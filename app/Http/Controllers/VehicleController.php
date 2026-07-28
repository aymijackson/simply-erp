<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VehicleController extends Controller
{
    public function index()
    {
        return view('vehicles.index');
    }

    public function datatable(Request $request)
    {
        $q = Vehicle::query()
            ->with(['company:id,name'])
            ->select(['id','company_id','registration_no','make','model','color','year','vin','is_active','created_at'])
            ->orderByDesc('id');

        if ($request->filled('is_active')) $q->where('is_active', (int)$request->is_active);
        if ($request->filled('company_id')) $q->where('company_id', (int)$request->company_id);

        return DataTables::eloquent($q)
            ->addColumn('company', fn($r) => $r->company?->name ?? '-')
            ->addColumn('status', fn($r) => $r->is_active ? 'Active' : 'Inactive')
            ->addColumn('created', fn($r) => $r->created_at ? $r->created_at->format('d-m-Y') : '-')
            ->addColumn('actions', fn($r) => view('vehicles.partials.actions', compact('r'))->render())
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'registration_no' => ['required','string','max:60','unique:vehicles,registration_no'],
            'company_id'      => ['nullable','integer','exists:companies,id'],
            'make'            => ['nullable','string','max:80'],
            'model'           => ['nullable','string','max:80'],
            'color'           => ['nullable','string','max:40'],
            'year'            => ['nullable','integer','min:1900','max:2100'],
            'vin'             => ['nullable','string','max:80'],
            'notes'           => ['nullable','string'],
            'is_active'       => ['nullable','boolean'],
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? true);

        Vehicle::create($data);

        return response()->json(['message' => 'Vehicle created.']);
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'registration_no' => ['required','string','max:60','unique:vehicles,registration_no,'.$vehicle->id],
            'company_id'      => ['nullable','integer','exists:companies,id'],
            'make'            => ['nullable','string','max:80'],
            'model'           => ['nullable','string','max:80'],
            'color'           => ['nullable','string','max:40'],
            'year'            => ['nullable','integer','min:1900','max:2100'],
            'vin'             => ['nullable','string','max:80'],
            'notes'           => ['nullable','string'],
            'is_active'       => ['nullable','boolean'],
        ]);

        $data['is_active'] = (bool)($data['is_active'] ?? false);

        $vehicle->update($data);

        return response()->json(['message' => 'Vehicle updated.']);
    }

    public function destroy(Vehicle $vehicle)
    {
        // If you want: block delete if attached to drivers
        if ($vehicle->drivers()->exists()) {
            return response()->json(['message' => 'Cannot delete: vehicle is assigned to drivers. Deactivate instead.'], 422);
        }

        $vehicle->delete();
        return response()->json(['message' => 'Vehicle deleted.']);
    }

    public function select2(Request $request)
    {
        $q = trim((string)$request->get('q', ''));

        $vehicles = Vehicle::query()
            ->where('is_active', true)
            ->when($q, function($qry) use ($q){
                $qry->where('registration_no','like',"%{$q}%")
                    ->orWhere('make','like',"%{$q}%")
                    ->orWhere('model','like',"%{$q}%")
                    ->orWhere('vin','like',"%{$q}%");
            })
            ->orderBy('registration_no')
            ->limit(20)
            ->get(['id','registration_no','make','model']);

        return $vehicles->map(fn($v) => [
            'id' => $v->id,
            'text' => trim($v->registration_no
                . (($v->make || $v->model) ? " • ".trim(($v->make ?? '').' '.($v->model ?? '')) : '')
            ),
        ]);
    }
}
