<?php
// Modules/Inventory/Http/Controllers/AgingController.php
namespace Modules\Inventory\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Inventory\Models\StockAge;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAgingController extends Controller
{
    public function index() { return view('inventory.stock.aging.index'); }

    public function datatable(Request $request)
    {
        $from = $request->input('from', 0);    // defaults
        $to   = $request->input('to', 30);

        $q = DB::table('v_stock_age')
        ->join('location_stores',  'location_stores.id',  '=', 'v_stock_age.location_store_id')
        ->join('product_variants', 'product_variants.id', '=', 'v_stock_age.product_variant_id')
        ->join('products',         'products.id',         '=', 'product_variants.product_id')   // <‑‑ add
        ->selectRaw('location_stores.name                                       AS store,
                    CONCAT(product_variants.sku, " – ", products.product_name) AS variant,
                    age_bucket,
                    SUM(qty)   AS qty,
                    SUM(value) AS value')
        ->whereBetween('age_days', [$from, $to])
        ->groupBy('store', 'variant', 'age_bucket');


        return 
            datatables()->query($q)
            ->addColumn('qty', fn($q)=> number_format($q->qty))
            ->addColumn('value', fn($q)=> number_format($q->value))
            ->make(true);
    }
}
