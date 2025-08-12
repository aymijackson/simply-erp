<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Modules\Inventory\Models\{StockEntry};
use Modules\Inventory\Services\CustomerReturnService;
use App\Models\LocationStore;
class CustomerReturnController extends Controller
{
    public function __construct(private CustomerReturnService $svc) {}

    /* -------------- UI -------------- */
    public function index()
    {
        $stores = LocationStore::orderBy('name')->get();
        return view('inventory.returns.customer..index', compact('stores'));
    }

    public function datatable()
    {
        $q = StockEntry::with(['store','customer'])
              ->where('entry_type','cust_return');

        return DataTables::eloquent($q)
            ->addColumn('store', fn($r)=> $r->store?->name)
            ->addColumn('customer', fn($r)=> $r->customer?->customer_name ?? '-')
            ->addColumn('status_badge', function($r){
                return view('partials.status-badge',['status'=>$r->status])->render();
            })
            ->addColumn('actions', fn($r)=>
                view('inventory.returns._actions',compact('r'))->render())
            ->rawColumns(['status_badge','actions'])
            ->make();
    }

    /* -------------- CRUD -------------- */
    public function store(Request $req)
    {
        $data = $this->validateHdr($req);
        $lines= $this->validateLines($req);

        $ret = $this->svc->create($data,$lines);
        return response()->json(['message'=>'Return saved','id'=>$ret->id]);
    }

    public function show(StockEntry $return)
    {
        abort_unless($return->entry_type === 'cust_return',404);
        return view('inventory.returns.show', compact('return'));
    }

    public function update(Request $req, StockEntry $return)
    {
        abort_unless($return->entry_type === 'cust_return',404);
        $return->update($this->validateHdr($req));
        /* lines update logic similar to store … */
        return response()->json(['message'=>'Updated']);
    }

    public function destroy(StockEntry $return)
    {
        abort_if($return->status!=='draft',400,'Only draft can be deleted');
        $return->delete();
        return response()->json(['message'=>'Deleted']);
    }

    /* actions */
    public function approve(StockEntry $return)
    {
        $this->svc->approve($return);
        return response()->json(['message'=>'Approved']);
    }
    public function post(StockEntry $return)
    {
        $this->svc->post($return);
        return response()->json(['message'=>'Posted']);
    }

    /* -------------- AJAX helpers -------------- */
    public function select2Variants(Request $r)
    {
        return app('Modules\Inventory\Http\Controllers\ProductVariantController')
               ->select2($r);     // reuse existing endpoint
    }
    public function select2Customers(Request $r)
    {
        $term = $r->q;
        $data = \Modules\CRM\Models\Customer::query()
                 ->when($term, fn($q)=>$q->where('customer_name','like',"%$term%"))
                 ->orderBy('customer_name')
                 ->limit(20)
                 ->get(['id','customer_name as text']);

        return response()->json($data);
    }

    /* -------------- validators -------------- */
    private function validateHdr(Request $r): array
    {
        return $r->validate([
            'entry_date'   => ['required','date'],
            'store_id'=> ['required','exists:location_stores,id'],
            'customer_id'  => ['required','exists:customers,id'],
            'reason'       => ['nullable','string','max:255'],
            'reference'    => ['nullable','string','max:255'],
        ]);
    }
    private function validateLines(Request $r): array
    {
        return $r->validate([
            'lines'                   => ['required','array','min:1'],
            'lines.*.product_variant_id'=> ['required','exists:product_variants,id'],
            'lines.*.qty'             => ['required','numeric','gt:0'],
            'lines.*.unit_cost'       => ['nullable','numeric','gte:0'],
        ])['lines'];
    }
}
