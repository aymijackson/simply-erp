<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DriverController extends Controller
{
    public function index()
    {
        return view('drivers.index');
    }

    public function datatable(Request $request)
    {
        $q = Driver::query()
            ->with(['company:id,name','user:id,name,email','vehicles:id,registration_no'])
            ->select([
                'id','first_name','last_name','phone','email','license_no',
                'company_id','user_id','is_active','created_at'
            ])
            ->orderByDesc('id');

        if ($request->filled('is_active')) {
            $q->where('is_active', (int)$request->is_active);
        }
        if ($request->filled('company_id')) {
            $q->where('company_id', (int)$request->company_id);
        }

        return DataTables::eloquent($q)
            ->addColumn('full_name', fn($r) => $r->full_name)
            ->addColumn('company', fn($r) => $r->company?->name ?? '-')
            ->addColumn('user', function($r){
                if (!$r->user) return '-';
                $name = $r->user->name ?? 'User';
                $email = $r->user->email ?? '';
                return trim($name . ($email ? " ({$email})" : ""));
            })
            ->addColumn('vehicles', function($r){
                $list = $r->vehicles->pluck('registration_no')->take(3)->implode(', ');
                $more = $r->vehicles->count() > 3 ? ' +' . ($r->vehicles->count()-3) : '';
                return $list ? $list.$more : '-';
            })
            ->addColumn('status', fn($r) => $r->is_active ? 'Active' : 'Inactive')
            ->addColumn('created', fn($r) => $r->created_at ? $r->created_at->format('d-m-Y') : '-')
            ->addColumn('actions', fn($r) => view('drivers.partials.actions', compact('r'))->render())
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required','string','max:150'],
            'last_name'  => ['nullable','string','max:150'],
            'phone'      => ['nullable','string','max:40'],
            'email'      => ['nullable','email','max:191'],
            'license_no' => ['nullable','string','max:80'],
            'company_id' => ['nullable','integer','exists:companies,id'],
            'user_id'    => ['nullable','integer','exists:users,id','unique:drivers,user_id'],
            'is_active'  => ['nullable','boolean'],
            'vehicle_ids'   => ['nullable','array'],
            'vehicle_ids.*' => ['integer','exists:vehicles,id'],
            'primary_vehicle_id' => ['nullable','integer','exists:vehicles,id'],
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? true);

        $driver = Driver::create($data);
        
        #   sync the vehicles
        $vehicleIds = $request->input('vehicle_ids', []) ?: [];
        $primaryId  = $request->input('primary_vehicle_id');
        
        $driver->vehicles()->sync($vehicleIds);
        
        // set primary (optional)
        if ($primaryId && in_array((int)$primaryId, array_map('intval', $vehicleIds), true)) {
            // reset all primary
            $driver->vehicles()->updateExistingPivot($vehicleIds, ['is_primary' => 0], false);
            $driver->vehicles()->updateExistingPivot($primaryId, ['is_primary' => 1, 'assigned_at' => now()], false);
        }


        return response()->json([
            'message' => 'Driver created.',
            'id'      => $driver->id,
        ]);
    }

    public function update(Request $request, Driver $driver)
    {
        $data = $request->validate([
            'first_name' => ['required','string','max:150'],
            'last_name'  => ['nullable','string','max:150'],
            'phone'      => ['nullable','string','max:40'],
            'email'      => ['nullable','email','max:191'],
            'license_no' => ['nullable','string','max:80'],
            'company_id' => ['nullable','integer','exists:companies,id'],
            'user_id'    => ['nullable','integer','exists:users,id','unique:drivers,user_id,'.$driver->id],
            'is_active'  => ['nullable','boolean'],
            'vehicle_ids'   => ['nullable','array'],
            'vehicle_ids.*' => ['integer','exists:vehicles,id'],
            'primary_vehicle_id' => ['nullable','integer','exists:vehicles,id'],
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? false);

        $driver->update($data);

        #   sync the vehicles
        $vehicleIds = $request->input('vehicle_ids', []) ?: [];
        $primaryId  = $request->input('primary_vehicle_id');
        
        $driver->vehicles()->sync($vehicleIds);
        
        if ($primaryId && in_array((int)$primaryId, array_map('intval', $vehicleIds), true)) {
            $driver->vehicles()->updateExistingPivot($vehicleIds, ['is_primary' => 0], false);
            $driver->vehicles()->updateExistingPivot($primaryId, ['is_primary' => 1], false);
        }

        return response()->json(['message' => 'Driver updated.']);
    }

    public function destroy(Driver $driver)
    {
        // Optional safety: block delete if assigned to deliveries
        $hasDeliveries = DB::table('sales_deliveries')->where('driver_id', $driver->id)->exists();
        if ($hasDeliveries) {
            return response()->json(['message' => 'Cannot delete: driver is assigned to deliveries. Deactivate instead.'], 422);
        }

        $driver->delete();

        return response()->json(['message' => 'Driver deleted.']);
    }

    public function select2(Request $request)
    {
        $q = trim((string)$request->get('q', ''));

        $drivers = Driver::query()
            ->where('is_active', true)
            ->when($q, function($qry) use ($q){
                $qry->where(function($w) use ($q){
                    $w->where('first_name','like',"%{$q}%")
                      ->orWhere('last_name','like',"%{$q}%")
                      ->orWhere('phone','like',"%{$q}%");
                });
            })
            ->orderBy('first_name')
            ->limit(20)
            ->get(['id','first_name','last_name','phone']);

        return $drivers->map(fn($d) => [
            'id' => $d->id,
            'text' => trim($d->full_name
                . ($d->phone ? " • {$d->phone}" : '')
            ),
        ]);
    }
}
