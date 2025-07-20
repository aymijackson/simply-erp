<?php

namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Production\Models\BOMItem;
use Modules\Production\Models\BillOfMaterial;
use Modules\Production\Models\RawMaterial;
use Yajra\DataTables\Facades\DataTables;

class BOMItemController extends Controller
{
    /**
     * Show the BOM Items page.
     */
    public function index()
    {
        $boms = BillOfMaterial::all();          // for dropdown
        $rawMaterials = RawMaterial::all();     // for dropdown
        return view('production.boms.items.index', compact('boms', 'rawMaterials'));
    }

    /**
     * Return JSON for DataTables.
     */
    public function datatable()
    {
        $query = BOMItem::with(['bom', 'raw_material']);
        
        return DataTables::of($query)
            ->addIndexColumn()               // <<< add this
            ->addColumn('checkbox', fn($item) =>
                '<input type="checkbox" class="row‑checkbox" value="'.$item->id.'">'
            )
            ->addColumn('bom_code', fn($item) =>
                $item->bom->code ?? '-'
            )
            ->addColumn('raw_material', fn($item) =>
                $item->raw_material->name ?? '-'
            )
            ->addColumn('actions', function ($row) {
                $data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                return '
                    <button class="btn btn-sm btn-info edit-bom-item" data-record="'.$data.'"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-bom-item" data-id="'.$row->id.'"><i class="fas fa-trash-alt"></i></button>
                ';
            })
            ->rawColumns(['checkbox','actions'])
            ->make(true);

    }

    /**
     * Store a new BOM item.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'bill_of_material_id'   => 'required|exists:bill_of_materials,id',
            'raw_material_id'       => 'required|exists:raw_materials,id',
            'quantity'              => 'required|numeric|min:0.0001',
        ]);

        BOMItem::create($data);

        return response()->json(['message' => 'BOM item added successfully']);
    }

    /**
     * Update an existing BOM item.
     */
    public function update(Request $request, BOMItem $bom_item)
    {
        $data = $request->validate([
            'bill_of_material_id'   => 'required|exists:bill_of_materials,id',
            'raw_material_id'       => 'required|exists:raw_materials,id',
            'quantity'              => 'required|numeric|min:0.0001',
        ]);

        $bom_item->update($data);

        return response()->json(['message' => 'BOM item updated successfully']);
    }

    /**
     * Delete a single BOM item.
     */
    public function destroy(BOMItem $bom_item)
    {
        $bom_item->delete();
        return response()->json(['message' => 'BOM item deleted']);
    }

    /**
     * Bulk delete selected BOM items.
     */
    public function bulkDelete(Request $request)
    {
        BOMItem::whereIn('id', $request->ids ?? [])->delete();
        return response()->json(['message' => 'Selected BOM items deleted']);
    }
}
