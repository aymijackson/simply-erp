<?php

namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Models\WorkOrder;
use Modules\Production\Models\BomItem;

class WorkOrderMaterialController extends Controller
{
    private function companyId(Request $r): int
    {
        return (int) ($r->user()->company_id ?? 1);
    }

    /**
     * Return JSON for DataTables.
     */
    public function bomItemsDatatable(Request $request, WorkOrder $work_order)
    {
        abort_unless($work_order->company_id == $this->companyId($request), 404);

        $bom_header_id = $work_order->bom_header_id;
        $query = BomItem::where(['bom_header_id' => $bom_header_id])->with(['bom', 'product_variant'])
            ->select('bom_items.*');
        
        return DataTables::of($query)
            ->addIndexColumn()               // <<< add this
            ->addColumn('product_variant', fn($item) =>
                $item->product_variant->product->product_name ?? '-'
            )
            ->addColumn('variant_sku', fn($item) =>
                $item->product_variant->sku ?? '-'
            )
            ->addColumn('product_name', fn($item) =>
                $item->product_variant->product->product_name ?? '-'
            )
            ->addColumn('qty_per_parent', fn($item) =>
                number_format($item->qty_per_parent, 2) ?? '-'
            )
            ->make(true);

    }

    public function datatable(int $wo, Request $r)
    {
        $workOrder = WorkOrder::findOrFail($wo);
        abort_unless($workOrder->company_id == $this->companyId($r), 404);

        $q = DB::table('work_order_materials as m')
            ->join('product_variants as v','v.id','=','m.product_variant_id')
            ->leftJoin('products as p','p.id','=','v.product_id')
            ->where('m.work_order_id', $wo)
            ->selectRaw("
               m.id, m.work_order_id, m.product_variant_id, m.planned_qty, m.issued_qty, m.returned_qty, m.notes,
               v.sku as sku, COALESCE(p.product_name,'') as name
            ");

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('remaining', fn($x) => number_format(($x->planned_qty - $x->issued_qty + $x->returned_qty), 4))
            ->addColumn('qty_planned', fn($x) => number_format($x->planned_qty, 4))
            ->addColumn('qty_issued',  fn($x) => number_format($x->issued_qty,  4))
            ->addColumn('qty_returned',fn($x) => number_format($x->returned_qty,4))
            ->addColumn('actions', function($x){
                // Hook your existing Stock Issue UI here (prefill from WO)
                return '
                  <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary issue-mat" data-id="'.$x->id.'">Issue</button>
                    <button class="btn btn-outline-secondary return-mat" data-id="'.$x->id.'">Return</button>
                  </div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
