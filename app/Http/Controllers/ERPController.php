<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
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
use Illuminate\Support\Facades\DB;

class ERPController extends Controller
{
    //
    public function index()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        /**
         * =========================
         * GOVERNANCE / ACCESS
         * =========================
         */
        $totalUsers = DB::table('users')->count();
        $adminUsers = DB::table('users')->where('can_access_admin', 1)->count();
        $erpUsers   = DB::table('users')->where('can_access_erp', 1)->count();
        $verifiedUsers = DB::table('users')->whereNotNull('email_verified_at')->count();

        $rolesCount = DB::table('roles')->count();
        $permissionsCount = DB::table('permissions')->count();
        $modulesCount = DB::table('modules')->count();

        $usersWithoutRoles = DB::table('users as u')
            ->leftJoin('model_has_roles as mhr', function ($join) {
                $join->on('mhr.model_id', '=', 'u.id')
                     ->where('mhr.model_type', '=', 'App\\Models\\User');
            })
            ->whereNull('mhr.role_id')
            ->count();

        $rolesWithoutPermissions = DB::table('roles as r')
            ->leftJoin('role_has_permissions as rhp', 'rhp.role_id', '=', 'r.id')
            ->whereNull('rhp.permission_id')
            ->count();

        $moduleAssignments = DB::table('module_user')->count(); // entitlements

        /**
         * =========================
         * BACKLOG / APPROVALS
         * =========================
         */
        $pendingActivities = DB::table('activities')->where('status', 'pending')->count();
        $overdueActivities = DB::table('activities')->where('status', 'overdue')->count();

        $pendingSalesOrders = DB::table('sales_orders')
            ->whereIn('status', ['draft', 'pending', 'submitted'])
            ->count();

        $pendingPurchaseOrders = DB::table('proc_purchase_orders')
            ->whereIn('status', ['draft', 'pending', 'submitted'])
            ->count();

        /**
         * =========================
         * INVENTORY HEALTH (views)
         * =========================
         * v_stock_levels: product_variant_id, location_store_id, qty_on_hand, value_on_hand
         * v_stock_age: product_variant_id, location_store_id, age_days, age_bucket, qty, value
         */
        $stockValue = (float) DB::table('v_stock_levels')->sum('value_on_hand');

        $lowStockCount = DB::table('v_stock_levels as v')
            ->join('product_variants as pv', 'pv.id', '=', 'v.product_variant_id')
            ->whereNotNull('pv.reorder_point')
            ->whereColumn('v.qty_on_hand', '<=', 'pv.reorder_point')
            ->count();

        $lowStockTop = DB::table('v_stock_levels as v')
            ->join('product_variants as pv', 'pv.id', '=', 'v.product_variant_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->select('p.product_name', 'pv.sku', 'v.qty_on_hand', 'pv.reorder_point')
            ->whereNotNull('pv.reorder_point')
            ->whereColumn('v.qty_on_hand', '<=', 'pv.reorder_point')
            ->orderBy('v.qty_on_hand')
            ->limit(8)
            ->get();

        $stockAgeBuckets = DB::table('v_stock_age')
            ->select('age_bucket', DB::raw('SUM(value) as total_value'))
            ->groupBy('age_bucket')
            ->orderBy('age_bucket')
            ->pluck('total_value', 'age_bucket');

        /**
         * =========================
         * SALES (for trend chart)
         * =========================
         */
        $salesLast6MonthsLabels = [];
        $salesLast6MonthsValues = [];

        for ($i = 5; $i >= 0; $i--) {
            $d = $now->copy()->subMonths($i);
            $salesLast6MonthsLabels[] = $d->format('M Y');

            $salesLast6MonthsValues[] = (float) DB::table('sales_invoices')
                ->whereNotNull('posted_at')
                ->whereBetween('posted_at', [$d->copy()->startOfMonth(), $d->copy()->endOfMonth()])
                ->sum('grand_total');
        }

        $salesThisMonth = (float) DB::table('sales_invoices')
            ->whereNotNull('posted_at')
            ->where('posted_at', '>=', $startOfMonth)
            ->sum('grand_total');

        /**
         * =========================
         * PRODUCTION
         * =========================
         */
        $openWorkOrders = DB::table('work_orders')
            ->whereIn('status', ['pending', 'in_progress', 'open'])
            ->count();

        $workOrderCostTotals = DB::table('v_work_order_costs')
            ->selectRaw('
                COALESCE(SUM(total_cost),0) as total_cost,
                COALESCE(SUM(labour_cost),0) as labour_cost,
                COALESCE(SUM(machine_cost),0) as machine_cost,
                COALESCE(SUM(logistics_cost),0) as logistics_cost,
                COALESCE(SUM(fuel_cost),0) as fuel_cost,
                COALESCE(SUM(service_cost),0) as service_cost,
                COALESCE(SUM(overhead_cost),0) as overhead_cost
            ')
            ->first();

        /**
         * =========================
         * HR / PAYROLL
         * =========================
         */
        $activeEmployees = DB::table('employees')->where('is_active', 1)->count();

        $todayAttendance = DB::table('attendances')
            ->whereDate('date', $now->toDateString())
            ->count();

        $pendingLeaves = DB::table('leaves')
            ->whereIn('status', ['pending', 'requested'])
            ->count();

        $pendingPayrolls = DB::table('payrolls')
            ->whereIn('status', ['pending', 'draft'])
            ->count();

        /**
         * =========================
         * CRM / SUPPORT
         * =========================
         */
        $openTickets = DB::table('support_tickets')
            ->whereIn('status', ['open', 'pending'])
            ->count();

        $highPriorityTickets = DB::table('support_tickets')
            ->whereIn('priority', ['high', 'urgent'])
            ->whereIn('status', ['open', 'pending'])
            ->count();

        $leadsNew = DB::table('leads')
            ->whereIn('status', ['new', 'open'])
            ->count();

        $opportunitiesOpen = DB::table('opportunities')
            ->whereIn('stage', ['prospecting', 'proposal', 'negotiation'])
            ->count();

        /**
         * =========================
         * SYSTEM HEALTH
         * =========================
         */
        $failedJobs = DB::table('failed_jobs')->count();
        $queuedJobs = DB::table('jobs')->count();

        $activeSessions = DB::table('sessions')
            ->where('last_activity', '>=', $now->copy()->subMinutes(30)->timestamp)
            ->count();

        /**
         * A few “recent” lists for admin visibility
         */
        $recentTickets = DB::table('support_tickets')
            ->select('id', 'subject', 'priority', 'status', 'created_at')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentActivities = DB::table('activities')
            ->select('id', 'activity_type', 'status', 'due_date', 'created_at')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return view('dashboard', compact(
            // Big headline KPIs (cards)
            'totalUsers', 'adminUsers', 'activeSessions', 'failedJobs',

            // Governance extras
            'erpUsers', 'verifiedUsers', 'rolesCount', 'permissionsCount', 'modulesCount',
            'usersWithoutRoles', 'rolesWithoutPermissions', 'moduleAssignments',

            // Backlog
            'pendingActivities', 'overdueActivities', 'pendingSalesOrders', 'pendingPurchaseOrders',

            // Inventory
            'stockValue', 'lowStockCount', 'lowStockTop', 'stockAgeBuckets',

            // Sales trend
            'salesThisMonth', 'salesLast6MonthsLabels', 'salesLast6MonthsValues',

            // Production
            'openWorkOrders', 'workOrderCostTotals',

            // HR
            'activeEmployees', 'todayAttendance', 'pendingLeaves', 'pendingPayrolls',

            // CRM/Support
            'openTickets', 'highPriorityTickets', 'leadsNew', 'opportunitiesOpen',

            // System
            'queuedJobs',

            // Recent lists
            'recentTickets', 'recentActivities'
        ));
    }
    
    public function index_old()
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
                <button class="btn btn-sm btn-primary edit-country" data-id="'.$country->id.'"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger delete-country" data-id="'.$country->id.'"><i class="fas fa-trash"></i></button>
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

    public function updateCountry(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'iso2'         => 'nullable|string|max:2',
            'iso3'         => 'nullable|string|max:3',
            'region_id'    => 'nullable|integer|exists:regions,id',
            'subregion_id' => 'nullable|integer|exists:subregions,id',
        ]);
    
        Country::updateOrCreate(
            ['id' => $request->id],
            $validated
        );
    
        return response()->json(['message' => 'Country saved successfully.']);
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

    public function updateCity(Request $request, $id)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id'   => 'required|exists:states,id',
        ]);
    
        $city = City::findOrFail($id);
    
        $city->update($validated);
    
        return response()->json([
            'message' => 'City updated successfully',
            'city'    => $city
        ]);
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
            'companies'     => Company::all(),
            'countries'     => Country::with(['region', 'subregion'])
                                    ->select('id', 'name', 'region_id', 'subregion_id')
                                    ->get(),   // <-- REQUIRED
            'cities'        => [],
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
            ->addColumn('company', fn($location) => $location->company->name ?? '') // new column
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
        $location = Location::with([
            'company',
            'type',
            'city.state',
            'city.state.country',
        ])->findOrFail($id);
    
        $locationTypes = LocationType::orderBy('name')->get();
        return view('locations.show', compact('location', 'locationTypes'));
    }


    public function editLocation($id)
    {
        $location = Location::with(['city.state.country'])->findOrFail($id);
    
        $city = $location->city;
        $state = $city ? $city->state : null;
        $country = $state ? $state->country : null;
        
        return response()->json([
            'location' => [
                'id' => $location->id,
                'name' => $location->name,
                'company_id' => $location->company_id,
                'location_type_id' => $location->location_type_id,
                'city_id' => $city?->id,
                'city_name' => $city?->name,
                'state_id' => $state?->id,
                'state_name' => $state?->name,
                'country_id' => $country?->id,
                'country_name' => $country?->name,
                'address' => $location->address,
                'longitude' => $location->longitude,
                'latitude' => $location->latitude,
                'description' => $location->description,
            ]
        ]);
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
        $blocks = LocationBlock::query()
            ->where('location_id', $locationId)
            ->select(['id', 'name', 'created_at']);
    
        return DataTables::of($blocks)
            ->addColumn('created_at', function ($block) {
                return $block->created_at ? $block->created_at->format('Y-m-d H:i') : '-';
            })
            ->addColumn('actions', function ($block) {
                return '
                    <button type="button"
                        class="btn btn-sm btn-outline-primary edit-child"
                        data-entity="blocks"
                        data-id="' . $block->id . '"
                        data-value="' . e($block->name ?? '') . '">
                        <i class="fas fa-edit"></i>
                    </button>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
    
    public function locationFloors(Request $request, $locationId)
    {
        $floors = LocationBlockFloor::query()
            ->with('block:id,name')
            ->whereHas('block', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            })
            ->select(['id', 'location_block_id', 'name']);
    
        return DataTables::of($floors)
            ->addColumn('name', function ($floor) {
                return $floor->name ?? '-';
            })
            ->addColumn('block_name', function ($floor) {
                return optional($floor->block)->name ?? '-';
            })
            ->addColumn('actions', function ($floor) {
                return '
                    <button type="button"
                        class="btn btn-sm btn-outline-primary edit-child"
                        data-entity="floors"
                        data-id="' . $floor->id . '"
                        data-value="' . e($floor->name ?? '') . '"
                        data-block-id="' . ($floor->location_block_id ?? '') . '"
                        data-block-name="' . e(optional($floor->block)->name ?? '') . '">
                        <i class="fas fa-edit"></i>
                    </button>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
    
    public function locationRooms(Request $request, $locationId)
    {
        $rooms = LocationBlockFloorRoom::query()
            ->with('floor:id,name')
            ->whereHas('floor.block', function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            })
            ->select(['id', 'location_block_floor_id', 'name']);
    
        return DataTables::of($rooms)
            ->addColumn('name', function ($room) {
                return $room->name ?? '-';
            })
            ->addColumn('floor_name', function ($room) {
                return optional($room->floor)->name ?? '-';
            })
            ->addColumn('actions', function ($room) {
                return '
                    <button type="button"
                        class="btn btn-sm btn-outline-primary edit-child"
                        data-entity="rooms"
                        data-id="' . $room->id . '"
                        data-value="' . e($room->name ?? '') . '"
                        data-floor-id="' . ($room->location_block_floor_id ?? '') . '"
                        data-floor-name="' . e(optional($room->floor)->name ?? $room->floor_name ?? '') . '">
                        <i class="fas fa-edit"></i>
                    </button>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
    
    public function locationStores(Request $request, $locationId)
    {
        $stores = LocationStore::query()
            ->with([
                'room:id,name',
                'location:id,name'
            ])
            ->where(function ($query) use ($locationId) {
                $query->whereHas('room.floor.block', function ($q) use ($locationId) {
                    $q->where('location_id', $locationId);
                })->orWhere('location_id', $locationId);
            })
            ->select(['id', 'name', 'location_block_floor_room_id', 'location_id']);
    
        return DataTables::of($stores)
            ->addColumn('name', function ($store) {
                return $store->name ?? '-';
            })
            ->addColumn('room_name', function ($store) {
                if ($store->room) {
                    return $store->room->name;
                }
    
                return optional($store->location)->name ? '[Direct] '.optional($store->location)->name : '-';
            })
            ->addColumn('actions', function ($store) {
                return '
                    <button type="button"
                        class="btn btn-sm btn-outline-primary edit-child"
                        data-entity="stores"
                        data-id="' . $store->id . '"
                        data-value="' . e($store->name ?? '') . '"
                        data-room-id="' . ($store->location_block_floor_room_id ?? '') . '"
                        data-room-name="' . e(optional($store->room)->name ?? $store->room_name ?? '') . '">
                        <i class="fas fa-edit"></i>
                    </button>
                ';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
    
    public function locationShelves(Request $request, $locationId)
    {
        $shelves = StoreShelf::query()
            ->with('store:id,name')
            ->whereHas('store', function ($q) use ($locationId) {
                $q->where(function ($sub) use ($locationId) {
                    $sub->whereHas('room.floor.block', function ($x) use ($locationId) {
                        $x->where('location_id', $locationId);
                    })->orWhere('location_id', $locationId);
                });
            })
            ->select(['id', 'store_id', 'code']);
    
        return DataTables::of($shelves)
            ->addColumn('code', function ($shelf) {
                return $shelf->code ?? '-';
            })
            ->addColumn('store_name', function ($shelf) {
                return optional($shelf->store)->name ?? '-';
            })
            ->addColumn('actions', function ($shelf) {
                return '
                    <button type="button"
                        class="btn btn-sm btn-outline-primary edit-child"
                        data-entity="shelves"
                        data-id="' . $shelf->id . '"
                        data-value="' . e($shelf->code ?? '') . '"
                        data-store-id="' . ($shelf->store_id ?? '') . '"
                        data-store-name="' . e(optional($shelf->store)->name ?? $shelf->store_name ?? '') . '">
                        <i class="fas fa-edit"></i>
                    </button>
                ';
            })
            ->rawColumns(['actions'])
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
            ->addColumn('actions', function ($block) {
                return '<button type="button" class="btn btn-sm btn-outline-primary edit-child"
                    data-entity="blocks"
                    data-id="'.$block->id.'"
                    data-value="'.e($block->name ?? '').'">
                    <i class="fas fa-edit"></i>
                </button>';
            })
            ->rawColumns(['checkbox', 'actions'])
            ->make(true);
        }
    }
    
    #   for the general page
    public function locationBlocksDatatable(Request $request)
    {
        if (! $request->ajax()) {
            abort(404);
        }
    
        $blocks = LocationBlock::with('location')->select('location_blocks.*');
    
        return DataTables::of($blocks)
            ->addColumn('checkbox', function ($block) {
                return '<input type="checkbox" class="row-checkbox" name="location_block_checkbox[]" value="' . $block->id . '">';
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
    
            $rooms = LocationBlockFloorRoom::with([
                'floor.block.location' // FIXED
            ]);
    
            return DataTables::of($rooms)
                ->addColumn('name', fn($room) => $room->name ?? '')
                ->addColumn('location', fn($room) => $room->floor->block->location->name ?? '')
                ->addColumn('block', fn($room) => $room->floor->block->name ?? '')
                ->addColumn('floor', fn($room) => $room->floor->name ?? '')
                ->addColumn('checkbox', fn($room) =>
                    '<input type="checkbox" name="room_checkbox[]" value="' . $room->id . '">' // FIXED
                )
                ->addColumn('actions', fn($room) =>
                    view('locations.blocks.floors.rooms.actions', compact('room'))->render()
                )
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
            'name' => 'required|string|max:255',
            'location_block_floor_id' => 'required|exists:location_block_floors,id',
        ]);
    
        $room = LocationBlockFloorRoom::findOrFail($id);
    
        $room->update([
            'name' => $request->name,
            'location_block_floor_id' => $request->location_block_floor_id,
        ]);
    
        return response()->json(['message' => 'Room updated successfully.']);
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
                    return $store->location->name ?? '';
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
            Rule::unique('location_stores')
                ->ignore($store->id)
                ->where(function ($query) use ($request) {
                    // Handle NULL room safely
                    if ($request->location_block_floor_room_id) {
                        return $query->where('location_block_floor_room_id', $request->location_block_floor_room_id);
                    }
                    return $query->whereNull('location_block_floor_room_id');
                }),
        ],
        'location_id' => 'required|exists:locations,id',
        'location_block_floor_room_id' => 'nullable|exists:location_block_floor_rooms,id',
        'description' => 'nullable|string',
    ]);

    // Validate that the room belongs to the same location
    if ($request->location_block_floor_room_id) {
        $room = LocationBlockFloorRoom::find($request->location_block_floor_room_id);

        if ($room->location_id !== (int) $request->location_id) {
            return response()->json([
                'message' => 'Selected room does not belong to the chosen location.'
            ], 422);
        }
    }

    // Corrected update fields
    $store->update([
        'name' => $request->name,
        'location_id' => $request->location_id,
        'location_block_floor_room_id' => $request->location_block_floor_room_id,
        'description' => $request->description,
    ]);

    return response()->json(['message' => 'Store details updated successfully.']);
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
                    <button class="btn btn-sm btn-primary edit-region" data-id="' . $regions->id . '"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger delete-region" data-id="' . $regions->id . '"><i class="fas fa-trash"></i></button>
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
                ->addColumn('actions', fn($subregion) => '<button class="btn btn-sm btn-primary edit-subregion" data-id="'.$subregion->id.'"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-danger delete-subregion" data-id="'.$subregion->id.'"><i class="fas fa-trash"></i></button>')
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
