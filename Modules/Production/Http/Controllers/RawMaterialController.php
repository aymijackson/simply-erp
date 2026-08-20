<?php

namespace Modules\Production\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Production\Models\RawMaterial;
use Modules\Inventory\Models\Product\Unit;
use Yajra\DataTables\Facades\DataTables;

class RawMaterialController extends Controller
{
    private function companyId(Request $r): int
    {
        return (int) ($r->user()->company_id ?? 1);
    }

    public function index(Request $request)
    {
        $units = Unit::all();  // Get all units for dropdown
        return view('production.raw_materials.index', compact('units'));  // Blade to build later
    }

    /** DataTables JSON */
    public function datatable(Request $request)
    {
        return DataTables::of(RawMaterial::where('company_id', $this->companyId($request)))
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
            'code'        => 'required|string|max:50|unique:production_raw_materials,code',
            'unit_id'     => 'required|exists:units,id',
            'cost'=> 'nullable|numeric|min:0',
            'stock_quantity'=> 'nullable|numeric|min:0',
            'restock_level'=> 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $data['company_id'] = $this->companyId($request);

        RawMaterial::create($data);
        return response()->json(['message' => 'Raw material added']);
    }

    public function edit(Request $request, RawMaterial $raw_material)
    {
        abort_unless($raw_material->company_id == $this->companyId($request), 404);

        return response()->json($raw_material);
    }

    public function update(Request $request, RawMaterial $raw_material)
    {
        abort_unless($raw_material->company_id == $this->companyId($request), 404);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:production_raw_materials,code,' .$raw_material->id,
            'unit_id'     => 'required|exists:units,id',
            'cost'=> 'nullable|numeric|min:0',
            'restock_level'=> 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $raw_material->update($data);
        return response()->json(['message' => 'Raw material updated']);
    }

    public function destroy(Request $request, RawMaterial $raw_material)
    {
        abort_unless($raw_material->company_id == $this->companyId($request), 404);

        $raw_material->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function bulkDelete(Request $request)
    {
        RawMaterial::where('company_id', $this->companyId($request))
            ->whereIn('id', $request->ids ?? [])
            ->delete();
        return response()->json(['message' => 'Bulk delete done']);
    }
}
