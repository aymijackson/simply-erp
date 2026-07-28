<?php

namespace App\Http\Controllers;

use App\Models\LocationStore;
use App\Models\StoreShelf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class StoreShelfController extends Controller
{
    public function index()
    {
        $stores = LocationStore::orderBy('name')->get(['id', 'name']);

        return view('locations.stores.shelves.index', compact('stores'));
    }

    public function list(Request $request)
    {
        if (! $request->ajax()) {
            abort(404);
        }

        $query = StoreShelf::with('store')->select('store_shelves.*');

        return DataTables::of($query)
            ->addColumn('checkbox', function ($shelf) {
                return '
                    <div class="d-flex justify-content-center align-items-center">
                        <input type="checkbox"
                               class="form-check-input row-checkbox m-0"
                               name="shelf_checkbox[]"
                               value="' . $shelf->id . '">
                    </div>
                ';
            })
            ->addColumn('store', function ($shelf) {
                return e(optional($shelf->store)->name ?? '');
            })
            ->addColumn('code', function ($shelf) {
                return e($shelf->code ?? '');
            })
            ->addColumn('capacity', function ($shelf) {
                return $shelf->capacity !== null ? e($shelf->capacity) : '';
            })
            ->addColumn('description', function ($shelf) {
                return e($shelf->description ?? '');
            })
            ->addColumn('actions', function ($shelf) {
                return view('locations.stores.shelves.actions', compact('shelf'))->render();
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => ['required', 'exists:location_stores,id'],
            'code' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $exists = StoreShelf::where('store_id', $request->store_id)
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($request->code))])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A shelf with this code already exists for the selected store.'
            ], 422);
        }

        $shelf = StoreShelf::create([
            'store_id' => $request->store_id,
            'code' => trim($request->code),
            'capacity' => $request->capacity !== null && $request->capacity !== '' ? $request->capacity : null,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Shelf created successfully.',
            'data' => $shelf
        ]);
    }

    public function edit($id)
    {
        $shelf = StoreShelf::with('store')->findOrFail($id);

        return response()->json([
            'shelf' => [
                'id' => $shelf->id,
                'store_id' => (string) $shelf->store_id,
                'store_name' => optional($shelf->store)->name,
                'code' => $shelf->code,
                'capacity' => $shelf->capacity,
                'description' => $shelf->description,
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $shelf = StoreShelf::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'store_id' => ['required', 'exists:location_stores,id'],
            'code' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $exists = StoreShelf::where('store_id', $request->store_id)
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($request->code))])
            ->where('id', '!=', $shelf->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A shelf with this code already exists for the selected store.'
            ], 422);
        }

        $shelf->update([
            'store_id' => $request->store_id,
            'code' => trim($request->code),
            'capacity' => $request->capacity !== null && $request->capacity !== '' ? $request->capacity : null,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Shelf updated successfully.',
            'data' => $shelf
        ]);
    }

    public function destroy($id)
    {
        $shelf = StoreShelf::findOrFail($id);
        $shelf->delete();

        return response()->json([
            'message' => 'Shelf deleted successfully.'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || count($ids) === 0) {
            return response()->json([
                'message' => 'Please select at least one shelf.'
            ], 422);
        }

        StoreShelf::whereIn('id', $ids)->delete();

        return response()->json([
            'message' => 'Selected shelves deleted successfully.'
        ]);
    }
}