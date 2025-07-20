<?php

namespace Modules\Inventory\Http\Controllers;   

use Modules\Inventory\Services\TransferService;
use App\Http\Controllers\Controller;
use Modules\Inventory\Models\StockLevel;
use Modules\Inventory\Models\Product\ProductVariant;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Modules\Inventory\Models\StockTransfer;
use App\Models\LocationStore;

class StockTransferController extends Controller
{
    public function __construct(private TransferService $svc) { }

     /**
     * GET /admin/api/variants?q=abc
     * Returns lightweight JSON array for Select2
     */
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
            'sku'           => $v->sku,
            'product_name'  => $v->product->product_name,
        ]);
    }

    public function index()
    {
        return view('inventory.stock.transfers.index');
    }

    public function datatable()
    {
        $q = StockTransfer::with('fromStore','toStore')
                ->select('*');
        return datatables()->eloquent($q)
            ->addColumn('stores', fn($r)=>$r->fromStore->name.' → '.$r->toStore->name)
            ->addColumn('lines',  fn($r)=>$r->lines()->count())
            ->addColumn('actions', fn($r)=>'
               <a class="btn btn-sm btn-primary" href="'.route('admin.inventory.stock.transfers.edit',$r).'">Open</a>')
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        return view('inventory.stock.transfers.edit', [
            'transfer' => null,
            'stores'   => LocationStore::all()
        ]);
    }

    public function store(Request $r)
    {
        $trf = $this->svc->create($r->only('from_store_id','to_store_id','reason','requested_by'),
                                  $r->lines ?? []);
        return redirect()->route('admin.inventory.stock.transfers.edit',$trf)
                         ->with('ok','Draft created');
    }

    public function edit(StockTransfer $transfer)
    {
        return view('inventory.stock.transfers.edit', [
            'transfer'=>$transfer->load('lines.variant'),
            'stores'=>LocationStore::all()
        ]);
    }

    public function post(StockTransfer $transfer)
    {
        $this->svc->post($transfer);
        return back()->with('ok','Transfer posted');
    }

    public function destroy(StockTransfer $transfer)
    {
        abort_if($transfer->status==='posted',403,'Posted transfers cannot be deleted');
        $transfer->delete();
        return response()->json(['ok'=>true]);
    }
}
