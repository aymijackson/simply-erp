<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\Location;
use App\Models\LocationBlock;
use App\Models\LocationBlockFloor;
use App\Models\LocationBlockFloorRoom;
use App\Models\LocationType;
use App\Models\LocationStore;
use App\Models\Region;
use App\Models\Subregion;
use App\Models\State;
use App\Models\StoreShelf;
use DataTables;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class ERPController extends Controller
{
    //
    public function index()
    {
        return view('dashboard', [
            'title' => 'Dashboard',
            'activities' => [],
        ]);
    }

    public function getSubregions(Request $request, $id)
    {
        return Subregion::where('region_id', $id)
        ->select('id', 'name')
        ->orderBy('name')
        ->get();
    }

    public function searchCities(Request $request)
    {
        $term = $request->get('term');

        $cities = City::where('name', 'like', '%' . $term . '%')
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $cities->map(function ($city) {
                return [
                    'id' => $city->id,
                    'text' => $city->name, // ⚠️ must be 'text' not 'name'
                ];
            }),
        ]);
    }

    public function getRoomsByLocation($locationId)
    {
        $rooms = LocationBlockFloorRoom::whereHas('floor.block.location', function ($query) use ($locationId) {
            $query->where('id', $locationId);
        })->select('id', 'name')->get();

        return response()->json($rooms);
    }

    public function countriesIndex()
    {
        $regions = Region::with('subregions')->get(); // optional if you want to preload
        return view('countries.index', compact('regions'));
    }

    public function countriesList(Request $request)
    {
        if ($request->ajax()) {
            $countries = Country::with(['region', 'subregion'])->select('id', 'name', 'region_id', 'subregion_id');

    return DataTables::of($countries)
        ->addColumn('region', fn($country) => $country->region ? $country->region->name : '-')
        ->addColumn('subregion', fn($country) => $country->subregion ? $country->subregion->name : '-')
        ->addColumn('actions', function($country) {
            return '
                <button class="btn btn-sm btn-info edit-country" data-id="'.$country->id.'">Edit</button>
                <button class="btn btn-sm btn-danger delete-country" data-id="'.$country->id.'">Delete</button>
            ';
        })
        ->rawColumns(['actions'])
        ->make(true);
        }
    }

    public function storeCountry(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'iso2' => 'nullable|string|max:2',
            'iso3' => 'nullable|string|max:3',
            'region_id' => 'nullable|integer|exists:regions,id',
            'subregion_id' => 'nullable|integer|exists:subregions,id',
        ]);

        Country::updateOrCreate(['id' => $request->id], $validated);

        return response()->json(['message' => 'Country saved successfully.']);
    }

    public function editCountry($id)
    {
        $country = Country::findOrFail($id);
        return response()->json(['country' => $country]);
    }

    public function destroyCountry($id)
    {
        Country::findOrFail($id)->delete();
        return response()->json(['message' => 'Country deleted successfully.']);
    }

    public function citiesIndex()
    {
        $regions = Region::with('subregions')->get(); // optional if you want to preload
        return view('cities.index', compact('regions'));
    }

    public function citiesList(Request $request)
    {
        if ($request->ajax()) {
            $cities = City::with('state.country');
        
            return DataTables::of($cities)
                ->addColumn('state', fn($city) => $city->state->name ?? '')
                ->addColumn('country', fn($city) => $city->state?->country?->name ?? '')
                ->addColumn('checkbox', fn($city) => '<input type="checkbox" name="city_checkbox[]" value="' . $city->id . '">')
                ->addColumn('actions', fn($city) => view('cities.actions', compact('city'))->render())
        
                // Explicitly define how to filter relationship columns:
                ->filterColumn('state', function ($query, $keyword) {
                    $query->whereHas('state', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('country', function ($query, $keyword) {
                    $query->whereHas('state.country', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
        
                ->rawColumns(['checkbox', 'actions'])
                ->make(true);
        }        
        
    }

    public function storeCity(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'iso2' => 'nullable|string|max:2',
            'iso3' => 'nullable|string|max:3',
            'region_id' => 'nullable|integer|exists:regions,id',
            'subregion_id' => 'nullable|integer|exists:subregions,id',
        ]);

        City::updateOrCreate(['id' => $request->id], $validated);

        return response()->json(['message' => 'City saved successfully.']);
    }

    public function editCity($id)
    {
        $country = City::findOrFail($id);
        return response()->json(['country' => $country]);
    }

    public function destroyCity($id)
    {
        City::findOrFail($id)->delete();
        return response()->json(['message' => 'City deleted successfully.']);
    }
    
    public function locationTypesIndex()
    {
        return view('locations.types.index');
    }

    public function locationTypesList(Request $request)
    {
        if ($request->ajax()) {
            $location_types = LocationType::query();
            return DataTables::of($location_types)
                ->addColumn('checkbox', fn($location_type) => '<input type="checkbox" name="type_checkbox[]" value="' . $location_type->id . '">')
                ->addColumn('actions', fn($location_type) => view('locations.types.actions', compact('location_type'))->render())
                ->rawColumns(['checkbox', 'actions'])
                ->make(true);
        }
    }

    public function storeLocationType(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        LocationType::create($request->only('name', 'description'));

        return response()->json(['message' => 'Location type created successfully.']);
    }

    public function showLocationType($id)
    {
        $location_type = LocationType::with(['locations'])->findOrFail($id);
        return view('locations.types.show', compact('location_type'));
    }

    public function locationTypeLocations(LocationType $locationType)
    {
        $locations = Location::with('type')
            ->where('location_type_id', $locationType->id);

        return DataTables::of($locations)
            ->addColumn('type', fn($l) => '<a href="'.route('admin.locations.show', $l->id).'">'.ucfirst($l->type->name).'</a>')
            ->addColumn('city', fn($l) => $l->city ?? '-')
            ->addColumn('company', fn($l) => $l->company ?? '-')
            ->addColumn('coordinates', fn($l) => "{$l->latitude}, {$l->longitude}")
            ->addColumn('actions', fn($l) => view('locations.actions', compact('l'))->render())
            ->rawColumns(['type', 'actions'])
            ->make(true);
    }

    public function locationTypeBlocks(LocationType $locationType)
    {
        $blocks = LocationBlock::with('location')
            ->whereHas('location', fn($q) => $q->where('location_type_id', $locationType->id));

        return DataTables::of($blocks)
            ->addColumn('location', fn($b) => $b->location->name ?? '-')
            ->addColumn('name', fn($b) => $b->name)
            ->addColumn('created_at', fn($b) => $b->created_at->format('Y-m-d'))
            ->make(true);
    }

    public function locationTypeFloors(LocationType $locationType)
    {
        $floors = LocationBlockFloor::with('block.location')
            ->whereHas('block.location', fn($q) => $q->where('location_type_id', $locationType->id));

        return DataTables::of($floors)
            ->addColumn('location', fn($f) => $f->block->location->name ?? '-')
            ->addColumn('block', fn($f) => $f->block->name ?? '-')
            ->addColumn('name', fn($f) => $f->name)
            ->make(true);
    }

    public function locationTypeRooms(LocationType $locationType)
    {
        $rooms = LocationBlockFloorRoom::with('floor.block.location')
            ->whereHas('floor.block.location', fn($q) => $q->where('location_type_id', $locationType->id));

        return DataTables::of($rooms)
            ->addColumn('location', fn($r) => $r->floor->block->location->name ?? '-')
            ->addColumn('floor', fn($r) => $r->floor->name ?? '-')
            ->addColumn('name', fn($r) => $r->name)
            ->make(true);
    }

    public function locationTypeStores(LocationType $locationType)
    {
        $stores = LocationStore::with('room.floor.block.location')
            ->whereHas('room.floor.block.location', fn($q) => $q->where('location_type_id', $locationType->id))
            ->orWhereHas('location', fn($q) => $q->where('location_type_id', $locationType->id));

        return DataTables::of($stores)
            ->addColumn('location', fn($s) => $s->room->floor->block->location->name ?? $s->location->name ?? '-')
            ->addColumn('room', fn($s) => $s->room->name ?? '-')
            ->addColumn('name', fn($s) => $s->name)
            ->make(true);
    }

    public function locationTypeShelves(LocationType $locationType)
    {
        $shelves = StoreShelf::with('store.room.floor.block.location')
            ->whereHas('store.room.floor.block.location', fn($q) => $q->where('location_type_id', $locationType->id))
            ->orWhereHas('store.location', fn($q) => $q->where('location_type_id', $locationType->id));

        return DataTables::of($shelves)
            ->addColumn('location', fn($s) => $s->store->room->floor->block->location->name ?? $s->store->location->name ?? '-')
            ->addColumn('store', fn($s) => $s->store->name ?? '-')
            ->addColumn('code', fn($s) => $s->code)
            ->make(true);
    }

    public function editLocationType($id)
    {
        $type = LocationType::findOrFail($id);
        return response()->json(['type' => $type]);
    }

    public function updateLocationType(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $type = LocationType::findOrFail($id);
        $type->update($request->only('name', 'description'));

        return response()->json(['message' => 'Location type updated successfully.']);
    }

    public function destroyLocationType($id)
    {
        LocationType::findOrFail($id)->delete();
        return response()->json(['message' => 'Location type deleted.']);
    }

    public function bulkDeleteLocationTypes(Request $request)
    {
        LocationType::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected location types deleted.']);
    }

    public function locationsIndex()
    {
        return view('locations.index', [
            'locationTypes' => LocationType::all(),
            'companies' => Company::all(),
            'cities' => City::all(),
        ]);
    }

    public function locationsList(Request $request)
    {
        if ($request->ajax()) {
            $locations = Location::with(['type', 'city']);

            return DataTables::of($locations)
            ->addColumn('name', fn($location) => '<a href="'. '/admin/locations/'.$location->id.'">'. $location->name .'</a>' ?? '')
            ->addColumn('location_type', fn($location) => '<a href="'. '/admin/location_types/'.$location->id.'">'. $location->type->name .'</a>' ?? '')
            ->addColumn('city', fn($location) => $location->city->name ?? '')
            ->addColumn('company', fn($location) => $loc->company->name ?? '') // new column
            ->addColumn('coordinates', fn($location) => "{$location->latitude}, {$location->longitude}") // merged column
            ->addColumn('checkbox', fn($location) => '<input type="checkbox" name="location_checkbox[]" value="' . $location->id . '">')
            ->addColumn('actions', fn($location) => view('locations.actions', compact('location'))->render())
            ->rawColumns(['name', 'location_type', 'checkbox', 'actions'])
            ->make(true);
        }
    }

    public function storeLocation(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'location_type_id' => 'required|exists:location_types,id',
            'city_id' => 'required|exists:cities,id',
            'company_id' => 'required|exists:companies,id',
            'address' => 'nullable|string',
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'description' => 'nullable|string'
        ]);

        Location::create($request->all());

        return response()->json(['message' => 'Location created successfully.']);
    }

    public function showLocation($id)
    {
        $location = Location::with(['type', 'city', 'company'])->findOrFail($id);
        return view('locations.show', compact('location'));
    }

    public function editLocation($id)
    {
        $location = Location::findOrFail($id);
        return response()->json(['location' => $location]);
    }

    public function updateLocation(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'location_type_id' => 'required|exists:location_types,id',
            'city_id' => 'required|exists:cities,id',
            'company_id' => 'required|exists:companies,id',
            'address' => 'nullable|string',
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'description' => 'nullable|string'
        ]);

        $loc = Location::findOrFail($id);
        $loc->update($request->all());

        return response()->json(['message' => 'Location updated successfully.']);
    }

    public function destroyLocation($id)
    {
        Location::findOrFail($id)->delete();
        return response()->json(['message' => 'Location deleted.']);
    }

    public function bulkDeleteLocations(Request $request)
    {
        Location::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected locations deleted.']);
    }

    public function locationBlocks(Request $request, $locationId)
    {
        $blocks = LocationBlock::where('location_id', $locationId);

        return DataTables::of($blocks)
            ->addColumn('created_at', fn($block) => $block->created_at->format('Y-m-d H:i'))
            ->make(true);
    }

    public function locationFloors(Request $request, $locationId)
    {
        $floors = LocationBlockFloor::with('block')
            ->whereHas('block', fn($q) => $q->where('location_id', $locationId));

        return DataTables::of($floors)
            ->addColumn('block_name', fn($floor) => $floor->block->name ?? '-')
            ->addColumn('name', fn($floor) => $floor->name)
            ->make(true);
    }

    public function locationRooms(Request $request, $locationId)
    {
        $rooms = LocationBlockFloorRoom::with('floor.block')
            ->whereHas('floor.block', fn($q) => $q->where('location_id', $locationId));

        return DataTables::of($rooms)
            ->addColumn('floor_name', fn($room) => $room->floor->name ?? '-')
            ->addColumn('name', fn($room) => $room->name)
            ->make(true);
    }

    public function locationStores(Request $request, $locationId)
    {
        $stores = LocationStore::with('room.floor.block.location', 'location')
            ->where(function ($query) use ($locationId) {
                $query->whereHas('room.floor.block', fn($q) => $q->where('location_id', $locationId))
                      ->orWhere('location_id', $locationId);
            });

        return DataTables::of($stores)
            ->addColumn('name', fn($store) => $store->name)
            ->addColumn('room_name', fn($store) => $store->room->name ?? '-')
            ->make(true);
    }

    public function locationShelves(Request $request, $locationId)
    {
        $shelves = StoreShelf::with('store.room.floor.block.location', 'store.location')
            ->whereHas('store', function ($q) use ($locationId) {
                $q->whereHas('room.floor.block', fn($q) => $q->where('location_id', $locationId))
                  ->orWhere('location_id', $locationId);
            });

        return DataTables::of($shelves)
            ->addColumn('code', fn($shelf) => $shelf->code)
            ->addColumn('store_name', fn($shelf) => $shelf->store->name ?? '-')
            ->make(true);
    }

    public function locationBlocksIndex()
    {
        return view('locations.blocks.index', [
            'locations' => Location::all(),
        ]);
    }

    public function locationBlocksList(Request $request)
    {
        if ($request->ajax()) {
            $blocks = LocationBlock::with(['location']);
            return DataTables::of($blocks)
            ->addColumn('name', fn($block) => $block->name ?? '')
            ->addColumn('location', fn($block) => $block->location->name ?? '')
            ->addColumn('checkbox', fn($block) => '<input type="checkbox" name="location_checkbox[]" value="' . $block->id . '">')
            ->addColumn('actions', fn($block) => view('locations.blocks.actions', compact('block'))->render())
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
        }
    }

    public function storeLocationBlock(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'location_id' => 'required|exists:locations,id',
            'name' => 'required|string|max:255',
        ]);

        LocationBlock::create($request->all());

        return response()->json(['message' => 'Location block created successfully.']);
    }

    public function editLocationBlock($id)
    {
        $block = LocationBlock::findOrFail($id);
        return response()->json(['location_block' => $block]);
    }

    public function updateLocationBlock(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'location_id' => 'required|exists:locations,id',
            'name' => 'required|string|max:255',
        ]);

        $loc = Location::findOrFail($id);
        $loc->update($request->all());

        return response()->json(['message' => 'Location block updated successfully.']);
    }

    public function destroyLocationBlock($id)
    {
        LocationBlock::findOrFail($id)->delete();
        return response()->json(['message' => 'Location block deleted.']);
    }

    public function bulkDeleteLocationBlocks(Request $request)
    {
        Location::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected location blocks deleted.']);
    }


    public function locationBlockFloorsIndex()
    {
        return view('locations.blocks.floors.index', [
            'location_blocks' => LocationBlock::all(),
        ]);
    }

    public function locationBlockFloorsList(Request $request)
    {
        if ($request->ajax()) {
            $floors = LocationBlockFloorRoom::with(['floor']);
            return DataTables::of($floors)
            ->addColumn('name', fn($floor) => $floor->name ?? '')
            ->addColumn('location', fn($floor) => $floor->block->location->name ?? '')
            ->addColumn('block', fn($floor) => $floor->block->name ?? '')
            ->addColumn('checkbox', fn($floor) => '<input type="checkbox" name="floor_checkbox[]" value="' . $floor->id . '">')
            ->addColumn('actions', fn($floor) => view('locations.blocks.floors.actions', compact('floor'))->render())
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
        }
    }

    public function storeLocationBlockFloor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location_block_id' => 'required|exists:location_blocks,id',
            // Ensure the floor name is unique within the same block
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('location_block_floors')->where(function ($query) use ($request) {
                    return $query->where('location_block_id', $request->location_block_id);
                }),
            ],
        ]);
    
        LocationBlockFloor::create($request->all());
    
        return response()->json(['message' => 'Location block floor created successfully.']);
    }

    public function editLocationBlockFloor($id)
    {
        $loc = LocationBlockFloor::findOrFail($id);
        return response()->json(['location_block_floor' => $block]);
    }

    public function updateLocationBlockFloor(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'location_block_id' => 'required|exists:location_blocks,id',
            'name' => 'required|string|max:255',
        ]);

        $loc = LocationBlockFloor::findOrFail($id);
        $loc->update($request->all());

        return response()->json(['message' => 'Floor updated successfully.']);
    }

    public function destroyLocationBlockFloor($id)
    {
        LocationBlockFloor::findOrFail($id)->delete();
        return response()->json(['message' => 'Floor deleted.']);
    }

    public function bulkDeleteLocationBlockFloors(Request $request)
    {
        LocationBlockFloor::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected floors deleted.']);
    }

    public function locationBlockFloorRoomsIndex()
    {
        return view('locations.blocks.floors.rooms.index', [
            'location_block_floors' => LocationBlockFloor::all(),
        ]);
    }

    public function locationBlockFloorRoomsList(Request $request)
    {
        if ($request->ajax()) {
            $rooms = LocationBlockFloorRoom::with(['floor']);
            return DataTables::of($rooms)
            ->addColumn('name', fn($room) => $room->name ?? '')
            ->addColumn('location', fn($room) => $room->floor->block->location->name ?? '')
            ->addColumn('block', fn($room) => $room->floor->block->name ?? '')
            ->addColumn('floor', fn($room) => $room->floor->name ?? '')
            ->addColumn('checkbox', fn($room) => '<input type="checkbox" name="floor_checkbox[]" value="' . $room->id . '">')
            ->addColumn('actions', fn($room) => view('locations.blocks.floors.rooms.actions', compact('room'))->render())
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
        }
    }

    public function storeLocationBlockFloorRoom(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('location_block_floor_rooms')->where(function ($query) use ($request) {
                    return $query->where('location_block_floor_id', $request->floor_id);
                }),
            ],
            'location_block_floor_id' => 'required|exists:location_block_floors,id',
            'purpose' => 'nullable|string|max:255',
        ]);
    
        LocationBlockFloorRoom::create($request->all());
    
        return response()->json(['message' => 'Room created successfully.']);
    }

    public function editLocationBlockFloorRoom($id)
    {
        $room = LocationBlockFloorRoom::findOrFail($id);
        return response()->json(['location_block_floor_room' => $room]);
    }

    public function updateLocationBlockFloorRoom(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'location_block_id' => 'required|exists:location_blocks,id',
            'name' => 'required|string|max:255',
        ]);

        $room = Location::findOrFail($id);
        $room->update($request->all());

        return response()->json(['message' => 'Floor updated successfully.']);
    }

    public function destroyLocationBlockFloorRoom($id)
    {
        LocationBlockFloorRoom::findOrFail($id)->delete();
        return response()->json(['message' => 'Floor deleted.']);
    }

    public function bulkDeleteLocationBlockFloorRooms(Request $request)
    {
        LocationBlockFloorRoom::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => 'Selected floors deleted.']);
    }

    // GET /stores
    public function storesIndex()
    {
        return view('locations.stores.index', [
            'locations' => Location::all(),
            'location_rooms' => LocationBlockFloorRoom::all(),
        ]);
    }

    // POST /stores
    public function storeStore(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('location_stores')->where(function ($query) use ($request) {
                    return $query->where('location_id', $request->location_id);
                }),
            ],
            'location_id' => 'required|exists:locations,id',
            'location_block_floor_room_id' => 'nullable|exists:location_block_floor_rooms,id',
            'description' => 'nullable|string',
        ]);

        $store = LocationStore::create($request->only(['name', 'location_id', 'location_block_floor_room_id', 'description']));

        return response()->json([
            'message' => 'Store created successfully.',
            'store' => $store
        ]);
    }

    // GET /stores/list
    public function storesList(Request $request)
    {
        if ($request->ajax()) {
            $stores = LocationStore::with(['room.floor.block.location', 'location']); // Ensure both are loaded
        
            return DataTables::of($stores)
                ->addColumn('name', fn($store) => $store->name ?? '')
        
                ->addColumn('location', function ($store) {
                    return $store->room 
                        ? ($store->room->floor->block->location->name ?? '')
                        : ($store->location->name ?? '');
                })
        
                ->addColumn('block', function ($store) {
                    return $store->room->floor->block->name ?? '';
                })
        
                ->addColumn('floor', function ($store) {
                    return $store->room->floor->name ?? '';
                })
        
                ->addColumn('room', function ($store) {
                    return $store->room->name ?? '';
                })
        
                ->addColumn('checkbox', fn($store) => '<input type="checkbox" name="store_checkbox[]" value="' . $store->id . '">')
        
                ->addColumn('actions', fn($store) => view('locations.stores.actions', compact('store'))->render())
        
                ->rawColumns(['checkbox', 'actions'])
                ->make(true);
        }
        
    }

    // GET /stores/{id}/edit
    public function editStore($id)
    {
        $store = LocationStore::with('room')->findOrFail($id);
        return response()->json($store);
    }

    // PUT /stores/{id}
    public function updateStore(Request $request, $id)
    {
        $store = LocationStore::findOrFail($id);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('location_stores')->ignore($store->id)->where(function ($query) use ($request) {
                    return $query->where('location_block_floor_room_id  ', $request->location_block_floor_room_id);
                }),
            ],
            'location_id' => 'required|exists:locations,id',
            'location_block_floor_room_id' => 'required|exists:location_block_floor_rooms,id',
            'description' => 'nullable|string',
        ]);

        $store->update($request->only(['name', 'location_room_id', 'description']));

        return response()->json(['message' => 'Store updated successfully.']);
    }

    // POST /stores/bulk-delete
    public function bulkDeleteStores(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:location_stores,id',
        ]);

        LocationStore::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => 'Selected stores deleted successfully.']);
    }

    // DELETE /stores/{id}
    public function destroyStore($id)
    {
        $store = LocationStore::findOrFail($id);
        $store->delete();

        return response()->json(['message' => 'Store deleted successfully.']);
    }

    public function regionsIndex()
    {
        return view('regions.index');
    }

    public function regionsList(Request $request)
    {
        if ($request->ajax()) {
            $regions = Region::select('regions.*');
            return DataTables::of($regions)
            ->addColumn('checkbox', function ($regions) {
                return '<input type="checkbox" name="ids[]" value="' . $regions->id . '">';
            })
            ->addColumn('actions', function ($regions) {
                return '
                    <button class="btn btn-sm btn-warning edit-region" data-id="' . $regions->id . '">Edit</button>
                    <button class="btn btn-sm btn-danger delete-region" data-id="' . $regions->id . '">Delete</button>
                ';
            })
            ->rawColumns(['checkbox', 'actions'])
                ->make(true);
        }
    }

    public function storeRegion(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);
        Region::updateOrCreate(['id' => $request->id], $validated);
        return response()->json(['message' => 'Region saved successfully.']);
    }

    public function editRegion($id)
    {
        $region = Region::findOrFail($id);
        return response()->json(['region' => $region]);
    }

    /**
     * Update the specified company.
     */
    public function updateRegion(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $company = Region::findOrFail($id);
        $company->update($request->only('name'));
        // log the update activity here
        // activity()->log('Company updated: ' . $request->name);

        return response()->json(['message' => 'Region updated successfully!']);
    }

    /**
     * Bulk-delete selected companies.
     */
    public function bulkDeleteRegions(Request $request)
    {
        $ids = $request->ids;
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'No regions selected.'], 422);
        }

        Region::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected regions deleted successfully!']);
    }

    public function destroyRegion($id)
    {
        Region::findOrFail($id)->delete();
        return response()->json(['message' => 'Region deleted successfully.']);
    }

    public function shelvesIndex() {
        $stores = LocationStore::select('id', 'name')->get();
        return view('locations.stores.shelves.index', compact('stores'));
    }
    
    public function storeShelf(Request $request) {
        $request->validate([
            'code' => [
                'required', 'string', 'max:255',
                Rule::unique('store_shelves')->where(fn ($q) => $q->where('store_id', $request->store_id)),
            ],
            'store_id' => 'required|exists:location_stores,id',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer'
        ]);
    
        StoreShelf::create($request->only(['code', 'store_id', 'description', 'capacity']));
    
        return response()->json(['message' => 'Shelf created successfully.']);
    }
    
    public function shelvesList(Request $request)
    {
        $shelves = StoreShelf::with([
            'store.room.floor.block.location',
            'store.location'
        ]);
    
        return DataTables::of($shelves)
            ->addColumn('store', function ($shelf) {
                $store = $shelf->store;
                if (!$store) return '-';
    
                $base = $store->name;
    
                if ($store->room) {
                    $location = $store->room?->floor?->block?->location?->name ?? '';
                    $block = $store->room?->floor?->block?->name ?? '';
                    $floor = $store->room?->floor?->name ?? '';
                    $room = $store->room?->name ?? '';
    
                    return "{$base} (Location: {$location} | Block: {$block} | Floor: {$floor} | Room: {$room})";
                }
    
                $locationName = $store->location?->name ?? 'Unknown Location';
                return "{$base} ({$locationName})";
            })
            ->addColumn('code', fn($shelf) => $shelf->code)
            ->addColumn('capacity', fn($shelf) => $shelf->capacity ?? '-')
            ->addColumn('description', fn($shelf) => $shelf->description ?? '-')
            ->addColumn('checkbox', fn($shelf) => '<input type="checkbox" name="shelf_checkbox[]" value="' . $shelf->id . '">')
            ->addColumn('actions', fn($shelf) => view('locations.stores.shelves.actions', compact('shelf'))->render())
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
    }
    
    
    public function editShelf($id) {
        $shelf = StoreShelf::findOrFail($id);
        return response()->json($shelf);
    }
    
    public function updateShelf(Request $request, $id)
    {
        $shelf = StoreShelf::findOrFail($id);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('store_shelves')
                    ->ignore($shelf->id)
                    ->where(fn ($query) => $query->where('store_id', $request->store_id)),
            ],
            'store_id' => 'required|exists:location_stores,id',
            'description' => 'nullable|string',
            'capacity' => 'nullable|integer',
        ]);

        $shelf->update($validated);

        return response()->json([
            'message' => 'Shelf updated successfully.'
        ]);

    }

    public function bulkDeleteShelves(Request $request) {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:store_shelves,id',
        ]);
    
        StoreShelf::whereIn('id', $request->ids)->delete();
    
        return response()->json(['message' => 'Shelves deleted successfully.']);
    }
    
    public function destroyShelf($id) {
        StoreShelf::findOrFail($id)->delete();
        return response()->json(['message' => 'Shelf deleted successfully.']);
    }

    public function statesIndex()
    {
        return view('states.index');
    }

    public function statesList(Request $request)
    {
        if ($request->ajax()) {
            $states = State::with('country')->select('states.*');

        return DataTables::of($states)
            ->addColumn('checkbox', fn ($state) => '<input type="checkbox" name="state_checkbox[]" value="'.$state->id.'">')
            ->addColumn('country', fn ($state) => optional($state->country)->name)
            ->addColumn('actions', function ($state) {
                return view('states.actions', compact('state'))->render();
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
            }
    }

    public function storeState(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|integer|exists:countries,id',
        ]);

        State::updateOrCreate(['id' => $request->id], $validated);
        return response()->json(['message' => 'State saved successfully.']);
    }

    public function editState($id)
    {
        $state = State::findOrFail($id);
        return response()->json(['state' => $state]);
    }

    public function updateState(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|integer|exists:countries,id',
        ]);

        $state = State::findOrFail($id);
        $state->update($request->only('name', 'state_id'));

        return response()->json(['message' => 'State updated successfully!']);
    }

    /**
     * Bulk-delete selected states.
     */
    public function bulkDeleteStates(Request $request)
    {
        $ids = $request->ids;
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'No states selected.'], 422);
        }

        State::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected states deleted successfully!']);
    }

    public function destroyState($id)
    {
        State::findOrFail($id)->delete();
        return response()->json(['message' => 'State deleted successfully.']);
    }

    public function subregionsIndex()
    {
        return view('subregions.index');
    }

    public function subregionsList(Request $request)
    {
        if ($request->ajax()) {
            $subregions = Subregion::with('region')->select('subregions.*');
            return DataTables::of($subregions)
                ->addColumn('checkbox', fn($subregion) => '<input type="checkbox" name="ids[]" value="'.$subregion->id.'">')
                ->addColumn('region', fn($subregion) => $subregion->region?->name)
                ->addColumn('actions', fn($subregion) => '<button class="btn btn-sm btn-warning edit-subregion" data-id="'.$subregion->id.'">Edit</button><button class="btn btn-sm btn-danger delete-subregion" data-id="'.$subregion->id.'">Delete</button>')
                ->rawColumns(['actions', 'checkbox'])
                ->make(true);
        }
    }

    public function storeSubregion(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'region_id' => 'required|integer|exists:regions,id',
        ]);

        Subregion::updateOrCreate(['id' => $request->id], $validated);
        return response()->json(['message' => 'Subregion saved successfully.']);
    }

    public function editSubregion($id)
    {
        $subregion = Subregion::findOrFail($id);
        return response()->json(['subregion' => $subregion]);
    }

    public function updateSubregion(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'region_id' => 'required|integer|exists:regions,id',
        ]);

        $subregion = Subregion::findOrFail($id);
        $subregion->update($request->only('name', 'region_id'));

        return response()->json(['message' => 'Subregion updated successfully!']);
    }

    /**
     * Bulk-delete selected subregions.
     */
    public function bulkDeleteSubregions(Request $request)
    {
        $ids = $request->ids;
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'No subregion selected.'], 422);
        }

        SubRegion::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected subregions deleted successfully!']);
    }

    public function destroySubregion($id)
    {
        Subregion::findOrFail($id)->delete();
        return response()->json(['message' => 'Subregion deleted successfully.']);
    }
}
