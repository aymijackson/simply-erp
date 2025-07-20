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
                ->addColumn('permissions', function($role) {
                    return implode(', ', $role->permissions->pluck('name')->toArray());
                })
                ->addColumn('actions', function($role) {
                    return '
                        <button class="btn btn-warning btn-sm edit-role" data-id="'.$role->id.'">Edit</button>
                        <button class="btn btn-danger btn-sm delete-role" data-id="'.$role->id.'">Delete</button>
                    ';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        $permissions = Permission::all();
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
