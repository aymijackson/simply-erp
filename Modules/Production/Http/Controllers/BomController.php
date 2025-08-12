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

    public function store(Request $r)
    {
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
                Rule::unique('boms', 'name'),
            ];
        }

        if ($request->bom_code !== $bom->bom_code) {
            $rules['bom_code'] = [
                'required', 'string', 'max:60',
                Rule::unique('boms', 'bom_code'),
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
