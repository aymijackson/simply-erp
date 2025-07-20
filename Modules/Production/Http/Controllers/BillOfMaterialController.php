<?php
namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Production\Models\RawMaterial;
use Modules\Inventory\Models\Product\Unit;
use Yajra\DataTables\Facades\DataTables;
use Modules\Production\Models\BillOfMaterial;
use Modules\Inventory\Models\Product\Product;

class BillOfMaterialController extends Controller
{
    public function index()
    {
        $products = Product::select('id', 'product_name')->get();
        return view('production.boms.index', compact('products'));
    }

    public function datatable()
    {
        return DataTables::of(
            BillOfMaterial::with('product')->latest()
        )->addColumn('checkbox', fn($bom)=>
            '<input type="checkbox" class="row-checkbox" value="'.$bom->id.'">'
        )->addColumn('product', fn($bom)=> $bom->product->product_name ?? '-')
        ->addColumn('version', fn($bom)=> $bom->version)
        ->addColumn('status', fn($bom)=> $bom->status)
        ->addColumn('notes', fn($bom)=> $bom->notes)
        ->addColumn('created_at', function ($bom) {
            return $bom->created_at ? $bom->created_at->format('d-m-Y H:i a') : 'N/A';
        })
        ->addColumn('actions', function ($bom) {
            $data = htmlspecialchars(json_encode($bom), ENT_QUOTES, 'UTF-8');
            return '
                <button class="btn btn-sm btn-info edit-bill-of-material" data-record="'.$data.'"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-bill-of-material" data-id="'.$bom->id.'"><i class="fas fa-trash-alt"></i></button>
            ';
        })
        ->rawColumns(['checkbox','actions'])->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'version'    => 'nullable|string|max:50',
            'notes'      => 'nullable|string',
            'items'      => 'array',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity'        => 'required|numeric|min:0.0001',
        ]);

        $bom = BillOfMaterial::create($data);
        // create items
        foreach ($data['items'] ?? [] as $item) {
            $bom->items()->create($item);
        }

        return response()->json(['message' => 'BOM saved']);
    }

    public function update(Request $request, BillOfMaterial $bom)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'version' => 'nullable|string|max:50',
            'notes'   => 'nullable|string',
            'items'   => 'array',
            'items.*.id'              => 'nullable|exists:bom_items,id',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity'        => 'required|numeric|min:0.0001',
        ]);

        $bom->update($data);

        // Sync items (simple: delete + recreate)
        $bom->items()->delete();
        foreach ($data['items'] ?? [] as $item) {
            $bom->items()->create($item);
        }

        return response()->json(['message' => 'BOM updated']);
    }

    public function destroy(BillOfMaterial $bom)
    {
        $bom->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        BillOfMaterial::whereIn('id', $request->ids ?? [])->delete();
        return response()->json(['message' => 'Bulk delete done']);
    }
}
