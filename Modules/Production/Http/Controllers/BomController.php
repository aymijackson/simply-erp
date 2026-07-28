<?php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\Production\Models\RawMaterial;
use Modules\Inventory\Models\Product\Unit;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Models\{BomHeader,BomItem};
use Modules\Inventory\Models\Product\Product;


class BomController extends Controller
{
    // BOM headers
    public function otherSelect2(BomHeader $bom, Request $r)
    {
        $term    = trim($r->q ?? '');
        $exclude = (array) ($bom->id ?? []); // supports one or many
    
        return \Modules\Production\Models\BomHeader::query()
            ->when($exclude, fn ($q) => $q->whereNotIn('id', $exclude))
            ->when($term !== '', function ($q) use ($term) {
                $like = "%{$term}%";
                $q->where(function ($qq) use ($like) {
                    $qq->where('name', 'like', $like)
                      ->orWhere('bom_code', 'like', $like);
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($b) => [
                'id'   => $b->id,
                'text' => "{$b->name} {$b->bom_code}",
            ]);
    }

    // BOM headers
    public function select2(Request $r)
    { 
        return BomHeader::where('name','like','%'.$r->q.'%')
                ->orWhere('bom_code','like','%'.$r->q.'%')
                ->limit(20)
                ->get()
                ->map(fn($b)=> ['id'=>$b->id,'text'=>$b->name.' '. $b->bom_code]);
    }

    public function index()
    {
        $boms = BomHeader::with('product_variant')->paginate(20);
        return view('production.boms.index',compact('boms'));
    }

    /* -----------------------------------------------------------------
     |  AJAX  –  GET /admin/production/boms/datatable
     |------------------------------------------------------------------*/
     public function datatable(Request $request)
     {
         // eager-load the product once, and count the lines
         $q = BomHeader::with('product_variant')          // finished-goods product
                 ->withCount('items')       // items_count alias
                 ->select('bom_headers.*');
 
         return DataTables::eloquent($q)
             /* ------------ display columns ------------ */
             ->addColumn('product_name', function (BomHeader $b) {
                 // e.g. “SKU-123 – Widget XL”
                 return $b->product_variant->sku . ' – ' . $b->product_variant?->product->product_name;
             })
             ->addColumn('name', fn (BomHeader $b) => $b->name ?: '-')
             ->addColumn('item_count', fn (BomHeader $b) => number_format($b->items->count()))
 
             /* ------------ action buttons ------------ */
             ->addColumn('actions', function (BomHeader $b) {
                 return view('production.boms.partials.actions', ['b' => $b])->render();
             })
 
             /* ------------ allow search on product text ------------- */
             ->filterColumn('product_name', function ($query, $keyword) {
                 $query->whereHas('product', fn ($q) =>
                     $q->where('sku', 'like', "%$keyword%")
                       ->orWhere('product_name', 'like', "%$keyword%"));
             })
 
             ->rawColumns(['actions'])      // buttons contain HTML
             ->make();
     }

     /**
     * Return JSON for DataTables.
     */
    public function bom_items_datatable(Request $request, BomHeader $bom)
    {
        // Base + joins so we can sort/filter by SKU/Product and compute ext_cost in SQL
        $base = BomItem::query()
            ->from('bom_items')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'bom_items.product_variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'pv.product_id')
            ->where('bom_items.bom_header_id', $bom->id);

        // Main select with aliases the JS columns can use
        $query = (clone $base)
            ->select([
                'bom_items.*',
                'pv.sku as variant_sku',
                'p.product_name as product_name',
            ])
            // effective unit cost = bom_items.unit_cost OR variant price
            ->selectRaw('COALESCE(bom_items.unit_cost, pv.price, 0) as unit_cost_eff')
            ->selectRaw('(bom_items.qty_per_parent * COALESCE(bom_items.unit_cost, pv.price, 0)) as ext_cost');

        // Optional: apply global search to SKU/Product (Datatables search.value)
        if ($search = $request->input('search.value')) {
            $query->where(function($q) use ($search) {
                $q->where('pv.sku', 'like', "%{$search}%")
                ->orWhere('p.product_name', 'like', "%{$search}%");
            });
        }

        // Grand totals for this BOM (not page totals)
        $totals = (clone $base)
            ->selectRaw('SUM(bom_items.qty_per_parent) as qty_total')
            ->selectRaw('SUM(bom_items.qty_per_parent * COALESCE(bom_items.unit_cost, pv.price, 0)) as ext_total')
            ->first();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('checkbox', fn($r) =>
                '<input type="checkbox" class="row-checkbox" value="'.$r->id.'">'
            )
            ->editColumn('qty_per_parent', fn($r) => (float) $r->qty_per_parent)
            ->addColumn('unit_cost', fn($r) => (float) $r->unit_cost_eff)
            ->editColumn('ext_cost', fn($r) => (float) $r->ext_cost)
            ->addColumn('bom_code', fn() => $bom->bom_code ?? '-') // constant per page
            // Actions: keep payload slim; provide labels for Select2 prefill
            ->addColumn('actions', function ($r) use ($bom) {
                $payload = [
                    'id'                 => $r->id,
                    'bom_header_id'      => $bom->id,
                    'bom_code_text'      => ($bom->bom_code ? "#{$bom->bom_code}" : "BOM #{$bom->id}"),
                    'product_variant_id' => $r->product_variant_id,
                    'variant_label'      => trim(($r->variant_sku ?? '').' — '.($r->product_name ?? '')),
                    'qty_per_parent'     => (float) $r->qty_per_parent,
                    'unit_cost'          => (float) $r->unit_cost_eff,
                ];
                $data = e(json_encode($payload));
                return <<<HTML
                    <button class="btn btn-sm btn-info edit-bom-item" data-record="{$data}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-bom-item" data-id="{$r->id}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                HTML;
            })
            ->rawColumns(['checkbox','actions'])
            ->with([
                'totals' => [
                    'qty_per_parent' => (float) ($totals->qty_total ?? 0),
                    'ext_cost'       => (float) ($totals->ext_total ?? 0),
                ],
            ])
            ->make(true);
    }
    /**
     * Store a new BOM item.
     */
    public function store(Request $r)
    {
        $linesJson = $r->input('lines');
        if (is_string($linesJson)) {
            $decoded = json_decode($linesJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $r->merge(['lines' => $decoded]);
            }
        }

        $data = $r->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'name'           => 'required|string|max:255|unique:bom_headers,name',
            'bom_code'           => 'required|string|max:40|unique:bom_headers,bom_code',
            'description'        => 'nullable|string',
            'yield_qty'          => 'numeric|min:0.0001',
            'items.*.product_variant_id' => ['required','exists:product_variants,id'],
            'items.*.qty_per_parent'       => 'required|numeric|min:0.0001',
        ]);

        DB::transaction(function() use ($data){
            $items = $data['items']; unset($data['items']);
            $bom   = BomHeader::create($data);
            foreach ($items as $i) $bom->items()->create($i);
        });

        return back()->with('ok','BOM saved');
    }

     /**
     * Display a single Bill-of-Materials (read-only).
     */
    public function show(BomHeader $bom)
    {
        /*
         | We eager-load everything the Blade needs:
         |   • parent variant & its product
         |   • all component lines (+ each component variant & product)
         */
        $bom->load([
            'product_variant.product',            // parent FG
            'items.product_variant.product',             // component lines
            // 'createdBy', 'approvedBy'            // optional user relations
        ]);

        //$canEdit = auth()->user()->can('update', $bom) && $bom->status === 'draft';
        $canEdit = true;
        return view('production.boms.show', compact('bom','canEdit'));
    }

    public function update(Request $request, BomHeader $bom)
    {
        // we only add the unique rules when the incoming value differs
        $rules = [
            'description'               => 'nullable|string',
            'yield_qty'                 => 'numeric|min:0.0001',

            'items.*.id'                => 'sometimes',
            'items.*.product_variant_id'=> ['required', 'exists:product_variants,id'],
            'items.*.qty_per_parent'    => 'required|numeric|min:0.0001',
        ];

        if ($request->name !== $bom->name) {
            $rules['name'] = [
                'required', 'string', 'max:150',
                Rule::unique('bom_headers', 'name'),
            ];
        }

        if ($request->bom_code !== $bom->bom_code) {
            $rules['bom_code'] = [
                'required', 'string', 'max:60',
                Rule::unique('bom_headers', 'bom_code'),
            ];
        }

        $data = $request->validate($rules);

        DB::transaction(function() use ($data,$bom){
            $items = $data['items']; unset($data['items']);
            $bom->update($data);

            /* simple sync-all strategy */
            $bom->items()->delete();
            foreach ($items as $i) $bom->items()->create($i);
        });

        return back()->with('ok','Updated');
    }

    /* ---------- Approve & auto-issue ---------- */
    public function approve(Request $r, BomHeader $bom)
    {
        abort_if($bom->status==='approved',400,'Already done');

        $storeId = $r->validate(['store_id'=>'required|exists:location_stores,id'])['store_id'];
        $qtyWO   = $r->validate(['produce_qty'=>'required|numeric|min:0.0001'])['produce_qty'];

        app(\App\Services\Mfg\BomPostingService::class)
            ->approve($bom,$storeId,$qtyWO);

        return back()->with('ok','BOM approved & components issued');
    }
}
