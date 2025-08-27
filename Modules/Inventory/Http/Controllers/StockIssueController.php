<?php
// Modules/Inventory/Http/Controllers/AgingController.php
namespace Modules\Inventory\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Inventory\Models\StockAge;
use Modules\Production\Models\BomItem;
use App\Models\LocationStore;
use Modules\Inventory\Models\{
    StockIssue, StockTransaction, Product\ProductVariant
};
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Services\StockIssueService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
                ->orderBy('id', 'DESC')
                ->select(['id','issue_no','from_store_id','status','created_at']);
        return datatables()->eloquent($q)
            ->addColumn('posted_at', fn($r)=>$r->created_at->format('d-m-Y h:i a'))
            ->addColumn('store', fn($r)=>$r->fromStore->name)
            ->addColumn('actions', fn($r)=> view('inventory.stock.issues.partials.actions',compact('r')))
            ->make(true);
    }

    public function store(Request $r)
    {
        $hdr = DB::transaction(function () use ($r) {
            $hdr = StockIssue::create([
                'issue_no'          => $this->nextNumber(),
                'issue_type'        => $r->input('issue_type', 'normal'),
                'from_store_id'     => $r->input('from_store_id'),
                'reference'         => $r->input('reference'),
                'reason'            => $r->input('reason'),
                'requested_by'      => auth()->id(),
                'issue_date'        => $r->input('issue_date', now()),
                // header-level links only; no linking in drafts
                'bom_header_id'     => $r->input('bom_header_id'),
                'sales_delivery_id' => $r->input('sales_delivery_id'),
                'status'            => 'draft',
            ]);

            $lines = collect($r->input('lines', []))
                ->filter(fn ($ln) => !empty($ln['product_variant_id']))
                ->map(function ($ln) {
                    $qty       = (float) ($ln['qty'] ?? 0);
                    $unit_cost = (float) ($ln['unit_cost'] ?? 0);
                    return [
                        'product_variant_id' => (int) $ln['product_variant_id'],
                        'qty'                => $qty,
                        'unit_cost'          => $unit_cost,
                        'value'              => $qty * $unit_cost,
                    ];
                });

            if ($lines->isEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lines' => 'At least one valid line is required.',
                ]);
            }

            $hdr->lines()->createMany($lines->all());
            return $hdr;
        });

        return response()->json(['id' => $hdr->id, 'message' => 'Issue saved (draft).']);
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
    try {
        // The service handles: ensureLinks, deficit reconciliation,
        // availability guard, stock txns, and marking as posted.
        $service->post($issue->loadMissing('lines.variant'));

        $fresh = $issue->fresh(); // get updated status/posted_at
        return response()->json([
            'id'        => $fresh->id,
            'status'    => $fresh->status,
            'posted_at' => optional($fresh->posted_at)->toDateTimeString(),
            'message'   => 'Issue posted successfully.',
        ]);

    } catch (ValidationException $e) {
        // e.g., insufficient stock – returns 422 with messages
        throw $e;

    } catch (HttpException $e) {
        // e.g., “Only approved issues can be posted” (422) or “already posted” (400)
        return response()->json(['message' => $e->getMessage()], $e->getStatusCode());

    } catch (\Throwable $e) {
        report($e);
        return response()->json(['message' => 'Post failed', 'error' => $e->getMessage()], 500);
    }
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
            ->addColumn('value', fn($l)=> number_format($l->qty * $l->value, 2))
            ->make(true);
    }

}
