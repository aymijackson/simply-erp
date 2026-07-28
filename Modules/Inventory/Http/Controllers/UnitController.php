<?php
namespace Modules\Inventory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Inventory\Models\Product\Unit;
use Yajra\DataTables\DataTables;

class UnitController extends Controller
{
    /* =========================================================
     | Audit helper (consistent with Inventory pattern)
     ========================================================= */
    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        $module = 'inventory.units';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

    public function index()
    {
        $count = Unit::count();

        $this->audit(
            action: 'view.index',
            description: 'Viewed units index',
            subject: null,
            meta: ['total_units' => $count]
        );

        return view('inventory.units.index', compact('count'));
    }

    public function datatable(Request $request)
    {
        $units = Unit::select(['id', 'name', 'symbol']);

        return DataTables::of($units)
            ->addColumn('checkbox', fn($row) =>
                '<input type="checkbox" class="unit-checkbox" value="'.$row->id.'">'
            )
            ->addColumn('action', fn($row) => '
                <button class="btn btn-warning btn-sm edit-btn"
                    data-id="'.$row->id.'"
                    data-name="'.e($row->name).'"
                    data-symbol="'.e($row->symbol).'">Edit</button>
                <button class="btn btn-danger btn-sm delete-btn"
                    data-id="'.$row->id.'">Delete</button>
            ')
            ->rawColumns(['checkbox', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'symbol' => 'nullable|string|max:50',
        ]);

        $unit = Unit::create($validated);

        $this->audit(
            action: 'created',
            description: 'Created unit '.$unit->name,
            subject: $unit,
            meta: [
                'id'     => $unit->id,
                'name'   => $unit->name,
                'symbol' => $unit->symbol,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Unit created successfully.',
            'unit'    => $unit
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'symbol' => 'nullable|string|max:50',
        ]);

        $unit = Unit::findOrFail($id);

        // BEFORE snapshot
        $before = $unit->only(['name', 'symbol']);

        $unit->update($validated);

        // AFTER snapshot
        $after = $unit->fresh()->only(['name', 'symbol']);

        $this->audit(
            action: 'updated',
            description: 'Updated unit '.$unit->name,
            subject: $unit,
            meta: [
                'before' => $before,
                'after'  => $after,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Unit updated successfully.'
        ]);
    }

    public function destroy($id)
    {
        $unit = Unit::findOrFail($id);

        $meta = $unit->only(['id', 'name', 'symbol']);

        $unit->delete();

        $this->audit(
            action: 'deleted',
            description: 'Deleted unit '.$meta['name'],
            subject: null,
            meta: $meta
        );

        return response()->json([
            'success' => true,
            'message' => 'Unit deleted successfully.'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'exists:units,id',
        ]);

        $units = Unit::whereIn('id', $request->ids)->get();

        Unit::whereIn('id', $request->ids)->delete();

        $this->audit(
            action: 'bulk.deleted',
            description: 'Bulk deleted units',
            subject: null,
            meta: [
                'ids'   => $units->pluck('id'),
                'names' => $units->pluck('name'),
                'count' => $units->count(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Selected units deleted.'
        ]);
    }

    public function metrics()
    {
        $total = Unit::count();

        return response()->json(['total' => $total]);
    }
}
