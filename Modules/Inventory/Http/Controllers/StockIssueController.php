<?php
// Modules/Inventory/Http/Controllers/AgingController.php
namespace Modules\Inventory\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Inventory\Models\StockAge;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore;
use Modules\Inventory\Models\StockIssue;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Services\StockIssueService;

class StockIssueController extends Controller
{
    public function fetch_variants(Request $req)
    {
        $q = $req->get('q', '');

        $variants = ProductVariant::query()
            ->with('product:id,product_name')   // eager ‑ tiny payload
            ->when($q, function ($qry) use ($q) {
                $qry->where('sku',   'like', "%$q%")
                     ->orWhereHas('product', fn($p) => $p->where('product_name','like',"%$q%"));
            })
            ->orderBy('sku')
            ->limit(30)
            ->get(['id','product_id','sku']);   // select only needed cols

        /* transform to slim JSON that Select2 expects:
           [ { id:123, sku:'ABC‑001', product_name:'Widget A' }, … ]
        */
        return $variants->map(fn($v)=>[
            'id'            => $v->id,
            'text'  => $v->product->product_name.' - '.$v->sku,
        ]);
    }

    public function index()           
    { 
        $stores = LocationStore::orderBy('name')->get(['id','name']);
        return view('inventory.stock.issues.index', compact('stores')); 
    }

    public function datatable()
    {
        $q = StockIssue::with('fromStore')
              ->select(['id','issue_no','from_store_id','status','created_at']);
        return datatables()->eloquent($q)
            ->addColumn('posted_at', fn($r)=>$r->created_at->format('d-m-Y h:i a'))
            ->addColumn('store', fn($r)=>$r->fromStore->name)
            ->addColumn('actions', fn($r)=> view('inventory.stock.issues.partials.actions',compact('r')))
            ->make(true);
    }

    public function store(Request $r)
    {
        $hdr = StockIssue::create([
            'issue_no'      => $this->nextNumber(),
            'from_store_id' => $r->from_store_id,
            'reference'     => $r->reference,
            'reason'        => $r->reason,
            'requested_by'  => auth()->id()
        ]);
        foreach ($r->lines as $ln)
        {
            if($ln['unit_cost'])
            {
                $ln['unit_cost'] = (float)$ln['unit_cost'];  // ensure float
                $ln['value'] = $ln['qty'] * $ln['unit_cost']; // calculate value
            }
            else
            {
                $ln['unit_cost'] = 0.0;  // default to zero if not provided
            }
            $hdr->lines()->create($ln);
        }

        return response()->json(['id'=>$hdr->id,'message'=>'Issue saved (draft).']);
    }

    /* -----------------------------------------------------------------
     *  POST /stock-issues/{issue}/approve   (route name: inventory.stock.issues.approve)
     * ----------------------------------------------------------------*/
    public function approve(StockIssue $issue)
    {
        // rule: only drafts are eligible
        if ($issue->status !== 'draft') {
            return response()->json([
                'message' => 'Only draft issues can be approved'
            ], 422);
        }

        $issue->update([
            'status'       => 'approved',
            'approved_by'  => auth()->id(),
            'approved_at'  => now(),
        ]);

        return response()->json(['message' => 'Issue approved']);
    }

    /* -----------------------------------------------------------------
     *  POST /stock-issues/{issue}/post     (route name: inventory.stock.issues.post)
     * ----------------------------------------------------------------*/
    public function post(StockIssue $issue, StockIssueService $service)
    {
        // rule: must be approved first
        if ($issue->status !== 'approved') {
            return response()->json([
                'message' => 'Issue must be approved before it can be posted'
            ], 422);
        }

        DB::transaction(function () use ($issue, $service) {
            // write ledger rows & update variant balances
            $service->post($issue);

            // mark header as posted
            $issue->update([
                'status'     => 'posted',
                'posted_by'  => auth()->id(),
                'posted_at'  => now(),
            ]);
        });

        return response()->json(['message' => 'Issue posted']);
    }


    /** small helper */
    protected function nextNumber(): string
    {
        $seq  = 1 + (int)StockIssue::max(DB::raw('SUBSTRING(issue_no,5)'));
        return 'ISS-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    public function show(StockIssue $issue)
    {
        // eager‑load relationships for the view
        $issue->load([
            'fromStore',
            'lines.variant.product'          // variant & parent product names
        ]);

        return view('inventory.stock.issues.show', compact('issue'));
    }

    /** DataTable JSON for the lines grid */
    public function linesDatatable(StockIssue $issue)
    {
        $q = $issue->lines()->with('variant.product');

        return datatables()
            ->eloquent($q)
            ->addColumn('sku',   fn($l)=> $l->variant->sku)
            ->addColumn('name',  fn($l)=> $l->variant->product->product_name)
            ->addColumn('qty',   fn($l)=> number_format($l->qty, 3))
            ->addColumn('u_cost',fn($l)=> number_format($l->unit_cost, 2))
            ->addColumn('value', fn($l)=> number_format($l->qty * $l->unit_cost, 2))
            ->make(true);
    }

}
