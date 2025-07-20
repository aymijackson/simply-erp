<?php

namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Production\Models\RawMaterial;
use Modules\Inventory\Models\Product\Unit;
use Yajra\DataTables\Facades\DataTables;

class RawMaterialController extends Controller
{
    public function index()
    {
        $units = Unit::all();  // Get all units for dropdown
        return view('production.raw_materials.index', compact('units'));  // Blade to build later
    }

    /** DataTables JSON */
    public function datatable()
    {
        return DataTables::of(RawMaterial::query())
            ->addColumn('checkbox', fn($rm) =>
                '<input type="checkbox" class="row-checkbox" value="'.$rm->id.'">'
            )
            ->addColumn('created_at', function ($row) {
                return $row->created_at ? $row->created_at->format('d-m-Y H:i a') : 'N/A';
            })
            ->addColumn('unit', fn($row) => $row->unit->name ?? '-')
            ->addColumn('actions', function ($row) {
                $data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                return '
                    <button class="btn btn-sm btn-info edit-raw-material" data-record="'.$data.'"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-raw-material" data-id="'.$row->id.'"><i class="fas fa-trash-alt"></i></button>
                ';
            })
            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:raw_materials,code',
            'unit_id'     => 'required|exists:units,id',
            'cost'=> 'nullable|numeric|min:0',
            'stock_quantity'=> 'nullable|numeric|min:0',
            'restock_level'=> 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        RawMaterial::create($data);
        return response()->json(['message' => 'Raw material added']);
    }

    public function update(Request $request, RawMaterial $raw_material)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:raw_materials,code,' .$raw_material->id,
            'unit_id'     => 'required|exists:units,id',
            'cost'=> 'nullable|numeric|min:0',
            'restock_level'=> 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $rawMaterial->update($data);
        return response()->json(['message' => 'Raw material updated']);
    }

    public function destroy(RawMaterial $raw_material)
    {
        $raw_material->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        RawMaterial::whereIn('id', $request->ids ?? [])->delete();
        return response()->json(['message' => 'Bulk delete done']);
    }
}
