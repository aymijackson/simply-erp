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
     * GET /admin/production/work-orders/{workOrder}/materials/variants/available
     *
     * Query params:
     *   q      : search term (sku/product name)
     *   page   : page number (Select2 infinite scroll)
     *   except : optional variant id(s) to exclude (id or comma list)
     */
    public function variantsAvailableSelect2(WorkOrder $workOrder, Request $r)
    {
        // Adjust these table/column names if yours differ
        $tblBomItems   = 'bom_items';              // columns: bom_id, product_variant_id, qty_per_parent
        $tblVariants   = 'product_variants';       // columns: id, sku, product_id
        $tblProducts   = 'products';               // columns: id, product_name
        $tblMaterials  = 'work_order_materials';   // columns: work_order_id, product_variant_id, qty_planned, qty_issued, qty_returned

        $bomId = $workOrder->bill_of_material_id ?? $workOrder->bom_id ?? null;
        if (!$bomId) {
            return response()->json(['results' => [], 'pagination' => ['more' => false]]);
        }

        $term    = trim((string) $r->input('q', ''));
        $page    = max(1, (int) $r->input('page', 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;

        // optional exclusion(s)
        $except = $r->filled('except')
            ? collect(explode(',', (string) $r->input('except')))->filter()->map(fn($x)=> (int)$x)->all()
            : [];

        // Required quantity for this WO for each variant
        $requiredExpr = 'SUM(bi.qty_per_parent) * ' . (float) $workOrder->quantity_to_produce;

        // Aggregate current WO materials (allocated/issued/returned) per variant
        $womAggSql = sprintf(
            "SELECT product_variant_id,
                    SUM(qty_planned)  AS allocated,
                    SUM(qty_issued)   AS issued,
                    SUM(qty_returned) AS returned
             FROM %s
             WHERE work_order_id = %d
             GROUP BY product_variant_id",
            $tblMaterials,
            (int) $workOrder->id
        );

        $q = DB::table("$tblBomItems as bi")
            ->join("$tblVariants as pv", 'pv.id', '=', 'bi.product_variant_id')
            ->join("$tblProducts as p", 'p.id', '=', 'pv.product_id')
            ->leftJoin(DB::raw("($womAggSql) wom"), 'wom.product_variant_id', '=', 'bi.product_variant_id')
            ->where('bi.bom_id', $bomId)
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
                '.$requiredExpr.' as required_qty,
                COALESCE(wom.allocated,0) as allocated,
                COALESCE(wom.issued,0) as issued,
                COALESCE(wom.returned,0) as returned,
                ('.$requiredExpr.') - GREATEST(
                    COALESCE(wom.allocated,0),
                    GREATEST(0, COALESCE(wom.issued,0) - COALESCE(wom.returned,0))
                ) as remaining_qty
            ')
            // only variants with remaining > 0
            ->having('remaining_qty', '>', 0)
            ->orderBy('pv.sku')
            ->offset($offset)
            ->limit($perPage + 1); // fetch one extra to know if there's a next page

        $rows = $q->get();

        $more = $rows->count() > $perPage;
        if ($more) $rows = $rows->slice(0, $perPage);

        // map to Select2
        $results = $rows->map(function ($r) {
            $rem = (float) $r->remaining_qty;
            // clean number (avoid trailing zeros)
            $remTxt = rtrim(rtrim(number_format($rem, 6, '.', ''), '0'), '.');
            return [
                'id'   => (int) $r->id,
                'text' => "{$r->sku} — {$r->product_name} (rem: {$remTxt})",
                // optional extras if you want to use them on the client:
                'remaining' => $rem,
                'required'  => (float) $r->required_qty,
                'allocated' => (float) $r->allocated,
                'consumed'  => max(0.0, (float)$r->issued - (float)$r->returned),
            ];
        });

        return response()->json([
            'results'    => $results,
            'pagination' => ['more' => $more],
        ]);
    }
    
    public function datatable($workOrderId)
    {
        $q = WorkOrderMaterial::query()
            ->with(['productVariant.product'])
            ->where('work_order_id', $workOrderId);

        return DataTables::of($q)
            ->addColumn('sku', fn($r) => optional($r->productVariant)->sku ?: '—')
            ->addColumn('name', fn($r) => optional(optional($r->productVariant)->product)->product_name ?: '—')
            ->addColumn('qty_planned',   fn($r) => number_format((float)$r->qty_planned, 6))
            ->addColumn('qty_issued',    fn($r) => number_format((float)$r->qty_issued, 6))
            ->addColumn('qty_returned',  fn($r) => number_format((float)$r->qty_returned, 6))
            ->addColumn('remaining',     fn($r) => number_format((float)($r->qty_planned - $r->qty_issued + $r->qty_returned), 6))
            ->addColumn('notes',         fn($r) => e($r->note ?? ''))
            ->addColumn('actions', function ($r) {
                $payload = e(json_encode([
                    'id' => $r->id,
                    'product_variant_id' => $r->product_variant_id,
                    'variant_label' => optional($r->productVariant)->sku . ' — ' . optional(optional($r->productVariant)->product)->product_name,
                    'qty_planned' => (float)$r->qty_planned,
                    'note' => $r->note,
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
            'qty_planned'        => $data['qty_planned'],
            'qty_issued'         => 0,
            'qty_returned'       => 0,
            'note'               => $data['note'] ?? null,
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
        $minPlanned = max(0, (float)$material->qty_issued - (float)$material->qty_returned);
        if ((float)$data['qty_planned'] < $minPlanned) {
            return response()->json([
                'message' => "Cannot set planned below currently consumed qty (min {$minPlanned})."
            ], 422);
        }

        $material->update($data);
        return response()->json(['success'=>true,'message'=>'Material updated']);
    }

    // ---- DELETE ----
    public function destroy(WorkOrderMaterial $material)
    {
        if ($material->qty_issued > 0 || $material->qty_returned > 0) {
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
