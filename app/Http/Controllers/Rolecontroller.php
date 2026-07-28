<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use DataTables;

class RoleController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
    
            $roles = Role::with('permissions')->select('roles.*');
    
            return datatables()->of($roles)
                ->addColumn('permissions', function ($role) {
    
                    $names = $role->permissions->pluck('name')->toArray();
    
                    if (empty($names)) {
                        return "<span class='text-muted'>—</span>";
                    }
    
                    // Group by prefix before first dot (core, inventory, crm, hrm, etc.)
                    $grouped = collect($names)->groupBy(function ($p) {
                        return explode('.', $p)[0] ?? 'other';
                    })->map(fn ($items) => $items->values()->all());
    
                    // Chips (CORE 18, CRM 7, ...)
                    $chips = $grouped->map(function ($items, $group) {
                        $label = strtoupper($group);
                        $count = count($items);
                        return "<span class='perm-badge-chip'>{$label}<span class='perm-badge-count'>{$count}</span></span>";
                    })->values()->all();
    
                    // Optional: limit chips shown to keep table tidy
                    $maxChips = 4;
                    $shown = array_slice($chips, 0, $maxChips);
                    $remaining = count($chips) - count($shown);
    
                    $chipsHtml = implode(' ', $shown);
                    if ($remaining > 0) {
                        $chipsHtml .= " <span class='perm-badge-chip'>+{$remaining} more</span>";
                    }
    
                    // Details HTML for modal (escaped into data-details in actions)
                    $detailsHtml = $grouped->map(function ($items, $group) {
                        $label = strtoupper($group);
                        $lis = collect($items)->sort()->map(fn ($p) => "<li class='mb-1'><code>{$p}</code></li>")->implode('');
                        return "<div class='mb-3'><div class='fw-bold mb-1'>{$label}</div><ul class='mb-0 ps-3'>{$lis}</ul></div>";
                    })->implode('');
    
                    // Store details on a button so you can pop a modal from the table
                    $safeDetails = e($detailsHtml);
                    $safeName = e($role->name);
    
                    return "
                        <div class='d-flex flex-wrap gap-1 align-items-center'>
                            {$chipsHtml}
                            <button type='button'
                                    class='btn btn-sm btn-outline-primary ms-1 perm-view-btn'
                                    data-name='{$safeName}'
                                    data-details='{$safeDetails}'>
                                View
                            </button>
                        </div>
                    ";
                })
                ->addColumn('actions', function ($role) {
                    return '
                        <button class="btn btn-warning btn-sm edit-role" data-id="'.$role->id.'">Edit</button>
                        <button class="btn btn-danger btn-sm delete-role" data-id="'.$role->id.'">Delete</button>
                    ';
                })
                ->rawColumns(['permissions','actions'])
                ->make(true);
        }
    
        /**
         * IMPORTANT:
         * Your blade currently expects $permissions grouped by module:
         * @foreach($permissions as $module => $perms)
         * So we must pass grouped permissions, not Permission::all().
         */
        $permissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(function ($p) {
                return explode('.', $p->name)[0] ?? 'other';
            });
    
        return view('roles.index', compact('permissions'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json(['message' => 'Role created successfully!']);
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        return response()->json([
            'role' => $role,
            'rolePermissions' => $role->permissions->pluck('name')->toArray(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,'.$id,
            'permissions' => 'nullable|array',
        ]);

        $role = Role::findOrFail($id);
        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions ?? []);

        return response()->json(['message' => 'Role updated successfully!']);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully!']);
    }
}
