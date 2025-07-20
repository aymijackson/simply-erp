<?php

namespace Modules\Inventory\Http\Controllers;   

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\StockLevel;
use Modules\Inventory\Models\Product\ProductVariant;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class StockLevelController extends Controller
{
    /* blade page */
    public function index()
    {
        return view('inventory.stock.levels.index');
    }

    /* DataTables JSON */
    public function datatable(Request $r)
    {
        $q = StockLevel::with(['location_store:id,name',
                               'variant.product:id,product_name'])
              ->select('v_stock_levels.*');

        /* optional filters (store_id / variant_id) */
        if ($r->store_id)   $q->where('location_store_id',   $r->store_id);
        if ($r->variant_id) $q->where('product_variant_id', $r->variant_id);

        return datatables()->eloquent($q)
            ->addColumn('store',   fn($r)=>$r->location_store?->name)
            ->addColumn('variant', fn($r)=>$r->variant->sku.' – '.$r->variant->product->product_name)
            ->editColumn('qty_on_hand',    fn($r)=> number_format($r->qty_on_hand))
            ->editColumn('value_on_hand',  fn($r)=> number_format($r->value_on_hand,2))
            ->rawColumns(['store','variant'])
            ->make(true);
    }

    /* mini API for dashboard widgets */
    public function totals()
    {
        $tot = StockLevel::selectRaw('SUM(qty_on_hand) as qty, SUM(value_on_hand) as val')->first();
        return ['qty'=> (int) $tot->qty, 'value'=> (float) $tot->val];
    }

    public function lowStockLevelsIndex()
    {
        return view('inventory.stock.levels.low');
    }

    public function lowStockLevelsDatatable()
    {
        $q = ProductVariant::with('product.brand')   // eager stuff you need
              ->lowStock();

        return DataTables::eloquent($q)
            ->addColumn('product', fn($v) => $v->product->product_name)
            ->addColumn('brand',   fn($v) => $v->product->brand->brand_name ?? '-')
            ->addColumn('sku',     fn($v) => $v->sku)
            ->addColumn('qty',     fn($v) => number_format($v->stock_quantity))
            ->addColumn('rop',     fn($v) => number_format($v->reorder_point))
            ->make();
    }
}
