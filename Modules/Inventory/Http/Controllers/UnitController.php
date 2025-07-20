<?php
namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Models\Product\Unit;
use Yajra\DataTables\DataTables;

class UnitController extends Controller
{
    public function index()
    {
        $units_count = Unit::count();
        return view('inventory.units.index', compact('units_count'));
    }

    public function datatable(Request $request)
    {
        $units = Unit::select(['id', 'name', 'symbol']);
        return DataTables::of($units)
            ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="unit-checkbox" value="'.$row->id.'">')
            ->addColumn('action', function ($row) {
                return '
                    <button class="btn btn-warning btn-sm edit-btn" data-id="'.$row->id.'" data-name="'.$row->name.'" data-symbol="'.$row->symbol.'">Edit</button>
                    <button class="btn btn-danger btn-sm delete-btn" data-id="'.$row->id.'">Delete</button>
                ';
            })
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $unit = Unit::create($request->only('name', 'symbol'));

        return response()->json(['success' => true, 'message' => 'Unit created successfully.', 'unit' => $unit]);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $unit = Unit::findOrFail($id);
        $unit->update($request->only('name', 'symbol'));

        return response()->json(['success' => true, 'message' => 'Unit updated successfully.']);
    }

    public function destroy($id)
    {
        Unit::destroy($id);
        return response()->json(['success' => true, 'message' => 'Unit deleted successfully.']);
    }

    public function bulkDelete(Request $request)
    {
        Unit::whereIn('id', $request->ids)->delete();
        return response()->json(['success' => true, 'message' => 'Selected units deleted.']);
    }

    public function metrics()
    {
        return response()->json(['total' => Unit::count()]);
    }
}
