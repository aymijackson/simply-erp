<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\LocationBlock;
use App\Models\LocationBlockFloor;
use App\Models\LocationBlockFloorRoom;
use App\Models\LocationStore;
use App\Models\StoreShelf;
use DataTables;

class LocationController extends Controller
{
    
    
    public function editFloor($id)
    { 
        $floor = LocationBlockFloor::findOrFail($id);
        return response()->json(['floor' => $floor]);
    }
    
    public function locationBlockFloorsDatatable(Request $request)
    { 
        if ($request->ajax()) {
            $floors = LocationBlockFloor::with(['block']);
            return DataTables::of($floors)
            ->addColumn('name', fn($floor) => $floor->name ?? '')
            ->addColumn('location', fn($floor) => optional(optional($floor->block)->location)->name)
            ->addColumn('block', fn($floor) => $floor->block->name ?? '')
            ->addColumn('checkbox', fn($floor) => '<input type="checkbox" name="floor_checkbox[]" value="' . $floor->id . '">')
            ->addColumn('actions', fn($floor) => view('locations.blocks.floors.actions', compact('floor'))->render())
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
        }
    }
    
    public function update(Request $request, $id)
    {
        $location = Location::findOrFail($id);
    
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location_type_id' => ['required', 'exists:location_types,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
        ]);
    
        DB::transaction(function () use ($location, $validated) {
            $location->update([
                'name' => $validated['name'],
                'location_type_id' => $validated['location_type_id'],
                'city_id' => $validated['city_id'],
                'address' => $validated['address'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'description' => $validated['description'] ?? null,
            ]);
        });
    
        return response()->json([
            'status' => true,
            'message' => 'Location updated successfully.',
        ]);
    }

    
    public function updateBlock(Request $request, $id)
    {
        $block = LocationBlock::findOrFail($id);
    
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
    
        $block->update([
            'name' => $validated['name'],
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Block updated successfully.',
        ]);
    }
    
    public function updateFloor(Request $request, $id)
    {
        $floor = LocationBlockFloor::findOrFail($id);
    
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location_block_id' => ['required', 'integer'],
        ]);
    
        $floor->update([
            'name' => $validated['name'],
            'location_block_id' => $validated['location_block_id'],
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Floor updated successfully.',
        ]);
    }
    
    public function updateRoom(Request $request, $id)
    {
        $room = LocationBlockFloorRoom::findOrFail($id);
    
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location_block_floor_id' => ['required', 'integer'],
        ]);
    
        $room->update([
            'name' => $validated['name'],
            'location_block_floor_id' => $validated['location_block_floor_id'],
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Room updated successfully.',
        ]);
    }
    
    public function updateStore(Request $request, $id)
    {
        $store = LocationStore::findOrFail($id);
    
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location_block_floor_room_id' => ['nullable', 'integer'],
        ]);
    
        $store->update([
            'name' => $validated['name'],
            'location_block_floor_room_id' => $validated['location_block_floor_room_id'],
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Store updated successfully.',
        ]);
    }
    
    public function updateShelf(Request $request, $id)
    {
        $shelf = StoreShelf::findOrFail($id);
    
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:255'],
            'store_id' => ['required', 'integer'],
        ]);
    
        $shelf->update([
            'code' => $validated['code'],
            'store_id' => $validated['store_id'],
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Shelf updated successfully.',
        ]);
    }

    public function getCompanies(Request $request)
    {
            return response()->json(Company::where('name', 'like', '%'.$request->term .'%')->orderBy('name')->get());
    }
    
    public function getStates($country_id)
    {
        return response()->json(State::where('country_id', $country_id)->orderBy('name')->get());
    }

    public function getCities($state_id)
    {
        return response()->json(City::where('state_id', $state_id)->orderBy('name')->get());
    }

    public function shelvesByStore($storeId)
    {
        $shelves = StoreShelf::where('store_id', $storeId)->get(['id', 'name']);
        return response()->json($shelves);
    }
    
    public function fetchStores(Request $request)
    {
        $search = trim((string)($request->get('q') ?? $request->get('term') ?? ''));
        $page   = max(1, (int) $request->get('page', 1));
        $perPage = 20;

        // Optional filters (use if you want)
        $locationId = $request->get('location_id'); // e.g. filter stores by location
        $blockFloorRoomId = $request->get('location_block_floor_room_id');

        $query = DB::table('location_stores')
            ->select('id', 'name', 'location_id', 'location_block_floor_room_id')
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->when($blockFloorRoomId, fn($q) => $q->where('location_block_floor_room_id', $blockFloorRoomId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('id', $search); // allows searching by exact id
            })
            ->orderBy('name');

        // Pagination manually (Select2 prefers this style)
        $total = (clone $query)->count();
        $rows  = $query
            ->forPage($page, $perPage)
            ->get();

        return response()->json([
            'results' => $rows->map(function ($s) {
                return [
                    'id'   => $s->id,
                    'text' => $s->name, // ✅ Select2 shows this
                    // extra fields (optional)
                    'location_id' => $s->location_id,
                    'location_block_floor_room_id' => $s->location_block_floor_room_id,
                ];
            })->values(),
            'pagination' => [
                'more' => ($page * $perPage) < $total
            ],
        ]);
    }
    
    public function searchCountries(Request $request)
    {
        $term = trim((string) $request->get('term', ''));
    
        $query = Country::query();
    
        if ($term !== '') {
            $query->where('name', 'like', "%{$term}%");
        }
    
        $results = $query->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function ($country) {
                return [
                    'id' => $country->id,
                    'text' => $country->name,
                    'latitude' => $country->latitude ?? null,
                    'longitude' => $country->longitude ?? null,
                ];
            })
            ->values();
    
        return response()->json(['results' => $results]);
    }

    
    public function searchStates(Request $request)
    {
        $term = trim((string) $request->get('term', ''));
        $countryId = $request->get('country_id');
    
        $query = State::query();
    
        if (!empty($countryId)) {
            $query->where('country_id', $countryId);
        }
    
        if ($term !== '') {
            $query->where('name', 'like', "%{$term}%");
        }
    
        $results = $query->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function ($state) {
                return [
                    'id' => $state->id,
                    'text' => $state->name,
                    'latitude' => $state->latitude ?? null,
                    'longitude' => $state->longitude ?? null,
                ];
            })
            ->values();
    
        return response()->json(['results' => $results]);
    }

    
    public function searchCities(Request $request)
    {
        $term = trim((string) $request->get('term', ''));
        $countryId = $request->get('country_id');
        $stateId = $request->get('state_id');
    
        $query = City::query()->with('state');
    
        if (!empty($stateId)) {
            $query->where('state_id', $stateId);
        } elseif (!empty($countryId)) {
            $query->whereHas('state', function ($q) use ($countryId) {
                $q->where('country_id', $countryId);
            });
        }
    
        if ($term !== '') {
            $query->where('name', 'like', "%{$term}%");
        }
    
        $results = $query->orderBy('name')
            ->limit(50)
            ->get()
            ->map(function ($city) {
                return [
                    'id' => $city->id,
                    'text' => $city->name,
                    'latitude' => $city->latitude ?? null,
                    'longitude' => $city->longitude ?? null,
                ];
            })
            ->values();
    
        return response()->json(['results' => $results]);
    }


}
