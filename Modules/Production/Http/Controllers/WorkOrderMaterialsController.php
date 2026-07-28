<?php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Models\{ WorkOrder, WorkOrderMaterial };
use Modules\Product\Models\ProductVariant;

class WorkOrderMaterialsController extends Controller
{
    /**
     * Select2: BOM variants that still have remaining allocatable quantity
     * GET /admin/production/work-orders/{work_order}/materials/variants/available
     *
     * Query params:
     *   q      : search term (sku/product name)
     *   page   : page number (Select2 infinite scroll)
     *   except : optional variant id(s) to exclude (id or comma list)
     */
    public function variantsAvailableSelect2(WorkOrder $work_order, Request $r)
{
    $tblBomItems  = 'bom_items';            // bom_header_id, product_variant_id, qty_per_parent
    $tblVariants  = 'product_variants';     // id, sku, product_id
    $tblProducts  = 'products';             // id, product_name
    $tblMaterials = 'work_order_materials'; // work_order_id, product_variant_id, planned_qty, issued_qty, returned_qty

    $bomId = $work_order->bom_header_id ?? $work_order->bom_id ?? null;
    if (!$bomId) {
        return response()->json(['results' => [], 'pagination' => ['more' => false]]);
    }

    $term    = trim((string) $r->input('q', ''));
    $page    = max(1, (int) $r->input('page', 1));
    $perPage = 25;
    $offset  = ($page - 1) * $perPage;

    $except = $r->filled('except')
        ? collect(explode(',', (string) $r->input('except')))->filter()->map(fn($x)=>(int)$x)->all()
        : [];

    // aggregate materials FOR THIS WO (by variant)
    $womAggSql = sprintf(
        "SELECT product_variant_id,
                SUM(planned_qty)  AS allocated,
                SUM(issued_qty)   AS issued,
                SUM(returned_qty) AS returned
         FROM %s
         WHERE work_order_id = %d
         GROUP BY product_variant_id",
        $tblMaterials,
        (int) $work_order->id
    );

    // multiplier (qty to produce)
    $mult = (float) $work_order->quantity_to_produce; // may be 0 on draft WOs

    $q = DB::table("$tblBomItems as bi")
        ->join("$tblVariants as pv", 'pv.id', '=', 'bi.product_variant_id')
        ->join("$tblProducts as p", 'p.id', '=', 'pv.product_id')
        ->leftJoin(DB::raw("($womAggSql) wom"), 'wom.product_variant_id', '=', 'bi.product_variant_id')
        ->where('bi.bom_header_id', $bomId)
        ->when($term !== '', function ($qq) use ($term) {
            $qq->where(function ($w) use ($term) {
                $w->where('pv.sku', 'like', "%{$term}%")
                  ->orWhere('p.product_name', 'like', "%{$term}%");
            });
        })
        ->when(!empty($except), fn($qq) => $qq->whereNotIn('bi.product_variant_id', $except))
        ->groupBy('bi.product_variant_id', 'pv.sku', 'p.product_name', 'wom.allocated', 'wom.issued', 'wom.returned')
        ->selectRaw('
            bi.product_variant_id as id,
            pv.sku,
            p.product_name,

            -- BOM requirement per parent (exactly from bom_items)
            SUM(bi.qty_per_parent)                                as req_per_parent,

            -- WO totals (req, allocated, consumed)
            SUM(bi.qty_per_parent) * ?                            as req_total,
            COALESCE(wom.allocated,0)                             as allocated_total,
            GREATEST(0, COALESCE(wom.issued,0) - COALESCE(wom.returned,0)) as consumed_total,

            -- Convert totals back to per-parent (avoid divide-by-zero)
            CASE WHEN ? > 0 THEN COALESCE(wom.allocated,0) / ? ELSE 0 END as allocated_per_parent,
            CASE WHEN ? > 0 THEN GREATEST(0, COALESCE(wom.issued,0) - COALESCE(wom.returned,0)) / ? ELSE 0 END as consumed_per_parent,

            -- Remaining per parent (what you want to show)
            SUM(bi.qty_per_parent)
              - GREATEST(
                    CASE WHEN ? > 0 THEN COALESCE(wom.allocated,0) / ? ELSE 0 END,
                    CASE WHEN ? > 0 THEN GREATEST(0, COALESCE(wom.issued,0) - COALESCE(wom.returned,0)) / ? ELSE 0 END
                )                                                 as rem_per_parent,

            -- (also compute remaining total in case you need it)
            (SUM(bi.qty_per_parent) * ?)
              - GREATEST(
                    COALESCE(wom.allocated,0),
                    GREATEST(0, COALESCE(wom.issued,0) - COALESCE(wom.returned,0))
                )                                                 as rem_total
        ', [$mult, $mult, $mult, $mult, $mult, $mult, $mult, $mult, $mult, $mult])
        ->having('rem_per_parent', '>', 0)
        ->orderBy('pv.sku')
        ->offset($offset)
        ->limit($perPage + 1);

    $rows = $q->get();

    $more = $rows->count() > $perPage;
    if ($more) $rows = $rows->slice(0, $perPage);

    $results = $rows->map(function ($r) {
        // format per-parent values (what you care about)
        $remPP = (float) $r->rem_per_parent;
        $reqPP = (float) $r->req_per_parent;

        $fmt = fn($x) => rtrim(rtrim(number_format($x, 6, '.', ''), '0'), '.');

        return [
            'id'   => (int) $r->id,
            // show per-parent values in the label
            'text' => "{$r->sku} — {$r->product_name} (rem: {$fmt($remPP)} of {$fmt($reqPP)})",
            // extras if needed
            'remaining_per_parent' => $remPP,
            'required_per_parent'  => $reqPP,
            'remaining_total'      => (float) $r->rem_total,
            'required_total'       => (float) $r->req_total,
            'allocated_total'      => (float) $r->allocated_total,
            'consumed_total'       => (float) $r->consumed_total,
        ];
    });

    return response()->json(['results' => $results, 'pagination' => ['more' => $more]]);
}

    
    public function datatable($workOrderId)
    {
        $q = WorkOrderMaterial::query()
            ->with(['product_variant.product'])
            ->where('work_order_id', $workOrderId);

        return DataTables::of($q)
            ->addColumn('sku', fn($r) => optional($r->product_variant)->sku ?: '—')
            ->addColumn('name', fn($r) => optional(optional($r->product_variant)->product)->product_name ?: '—')
            ->addColumn('planned_qty',   fn($r) => number_format((float)$r->planned_qty, 2))
            ->addColumn('issued_qty',    fn($r) => number_format((float)$r->issued_qty, 2))
            ->addColumn('returned_qty',  fn($r) => number_format((float)$r->returned_qty, 2))
            ->addColumn('remaining',     fn($r) => number_format((float)($r->planned_qty - $r->issued_qty + $r->returned_qty), 2))
            ->addColumn('notes',         fn($r) => e($r->notes ?? ''))
            ->addColumn('actions', function ($r) {
                $payload = e(json_encode([
                    'id' => $r->id,
                    'product_variant_id' => $r->product_variant_id,
                    'variant_label' => optional($r->product_variant)->sku . ' — ' . optional(optional($r->product_variant)->product)->product_name,
                    'planned_qty' => (float)$r->planned_qty,
                    'note' => $r->notes,
                ]));
                return '<div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary issue-mat" data-id="'.$r->id.'" title="Issue"><i class="fas fa-arrow-up"></i></button>
                    <button class="btn btn-outline-secondary return-mat" data-id="'.$r->id.'" title="Return"><i class="fas fa-arrow-down"></i></button>
                    <button class="btn btn-warning edit-mat" data-record=\''.$payload.'\' title="Edit"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger  del-mat"  data-id="'.$r->id.'" title="Delete"><i class="fas fa-trash"></i></button>
                </div>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    // ---- CREATE ----
    public function store(Request $r, WorkOrder $workOrder)
    { 
        $data = $r->validate([
            'product_variant_id' => ['required','exists:product_variants,id',
                Rule::unique('work_order_materials', 'product_variant_id')->where(fn($q)=>$q->where('work_order_id',$workOrder->id))
            ],
            'qty_planned' => ['required','numeric','min:0.000001'],
            'note'        => ['nullable','string'],
        ]);

        WorkOrderMaterial::create([
            'work_order_id'      => $workOrder->id,
            'product_variant_id' => $data['product_variant_id'],
            'planned_qty'        => $data['qty_planned'],
            'issued_qty'         => 0,
            'returned_qty'       => 0,
            'notes'              => $data['note'] ?? null,
        ]);

        return response()->json(['success'=>true,'message'=>'Material added']);
    }

    // ---- UPDATE ----
    public function update(Request $r, WorkOrderMaterial $material)
    {
        $data = $r->validate([
            'product_variant_id' => ['required','exists:product_variants,id',
                Rule::unique('work_order_materials', 'product_variant_id')
                    ->where(fn($q)=>$q->where('work_order_id',$material->work_order_id))
                    ->ignore($material->id),
            ],
            'qty_planned' => ['required','numeric','min:0.000001'],
            'note'        => ['nullable','string'],
        ]);

        // Guard: planned cannot drop below (issued - returned)
        $minPlanned = max(0, (float)$material->issued_qty - (float)$material->returned_qty);
        if ((float)$data['qty_planned'] < $minPlanned) {
            return response()->json([
                'message' => "Cannot set planned below currently consumed qty (min {$minPlanned})."
            ], 422);
        }

        $material->update([
            'product_variant_id' => $data['product_variant_id'],
            'planned_qty'        => $data['qty_planned'],
            'notes'              => $data['note'] ?? null,
        ]);
        return response()->json(['success'=>true,'message'=>'Material updated']);
    }

    // ---- DELETE ----
    public function destroy(WorkOrderMaterial $material)
    {
        if ($material->issued_qty > 0 || $material->returned_qty > 0) {
            return response()->json(['message'=>'Cannot delete a line with any issue/return activity.'], 422);
        }
        $material->delete();
        return response()->json(['success'=>true,'message'=>'Material deleted']);
    }

    // ---- Select2: variants for this WO ----
    public function variantsSelect2(WorkOrder $workOrder, Request $r)
    {
        $term = trim((string)$r->input('q',''));

        $q = ProductVariant::query()
            ->select('product_variants.id','product_variants.sku','products.product_name')
            ->join('products','products.id','=','product_variants.product_id');

        if ($term !== '') {
            $q->where(function($qq) use ($term){
                $qq->where('product_variants.sku','like',"%{$term}%")
                   ->orWhere('products.product_name','like',"%{$term}%");
            });
        }

        // Optional: exclude variants already on this WO
        $existing = WorkOrderMaterial::where('work_order_id',$workOrder->id)->pluck('product_variant_id');
        if ($existing->isNotEmpty()) $q->whereNotIn('product_variants.id', $existing);

        $items = $q->orderBy('product_variants.sku')->limit(25)->get()
            ->map(fn($v)=> ['id'=>$v->id, 'text'=> "{$v->sku} — {$v->product_name}"]);

        return response()->json($items);
    }
}
