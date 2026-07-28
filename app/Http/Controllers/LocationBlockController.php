<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class LocationBlockController extends Controller
{
    public function index()
    {
        $locations = Location::orderBy('name')->get(['id', 'name']);

        return view('locations.blocks.index', compact('locations'));
    }

    public function datatable(Request $request)
    {
        if (! $request->ajax()) {
            abort(404);
        }

        $query = LocationBlock::with('location')->select('location_blocks.*');

        return DataTables::of($query)
            ->addColumn('checkbox', function ($block) {
                return '<input type="checkbox" class="form-check-input row-checkbox" name="location_block_checkbox[]" value="' . $block->id . '">';
            })
            ->addColumn('name', function ($block) {
                return e($block->name ?? '');
            })
            ->addColumn('location', function ($block) {
                return e(optional($block->location)->name ?? '');
            })
            ->addColumn('actions', function ($block) {
                return view('locations.blocks.actions', compact('block'))->render();
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'exists:locations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $exists = LocationBlock::where('location_id', $request->location_id)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A block with this name already exists for the selected location.'
            ], 422);
        }

        $block = LocationBlock::create([
            'name' => trim($request->name),
            'location_id' => $request->location_id,
        ]);

        return response()->json([
            'message' => 'Location block created successfully.',
            'data' => $block
        ]);
    }

    public function edit($id)
    {
        $block = LocationBlock::with('location')->findOrFail($id);

        return response()->json([
            'location_block' => [
                'id' => $block->id,
                'name' => $block->name,
                'location_id' => (string) $block->location_id,
                'location_name' => optional($block->location)->name,
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $block = LocationBlock::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'exists:locations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $exists = LocationBlock::where('location_id', $request->location_id)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])
            ->where('id', '!=', $block->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'A block with this name already exists for the selected location.'
            ], 422);
        }

        $block->update([
            'name' => trim($request->name),
            'location_id' => $request->location_id,
        ]);

        return response()->json([
            'message' => 'Location block updated successfully.',
            'data' => $block
        ]);
    }

    public function destroy($id)
    {
        $block = LocationBlock::findOrFail($id);
        $block->delete();

        return response()->json([
            'message' => 'Location block deleted successfully.'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || count($ids) === 0) {
            return response()->json([
                'message' => 'Please select at least one block.'
            ], 422);
        }

        LocationBlock::whereIn('id', $ids)->delete();

        return response()->json([
            'message' => 'Selected blocks deleted successfully.'
        ]);
    }
}