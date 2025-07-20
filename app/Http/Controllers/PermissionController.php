<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use DataTables;

class PermissionController extends Controller
{
    /**
     * Display the permissions list or return JSON for DataTables.
     */
    public function index(Request $request)
{
    if ($request->ajax()) {
        $permissions = Permission::select('id','name');

        return Datatables::of($permissions)
            ->addColumn('checkbox', function ($perm) {
                return '<input type="checkbox" name="ids[]" value="'.$perm->id.'">';
            })
            ->addColumn('actions', function ($perm) {
                return '
                  <button class="btn btn-warning btn-sm edit-permission" data-id="'.$perm->id.'">Edit</button>
                  <button class="btn btn-danger btn-sm delete-permission" data-id="'.$perm->id.'">Delete</button>
                ';
            })
            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    return view('permissions.index');
}
    /**
     * Store a newly created permission.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        Permission::create(['name' => $request->name]);

        return response()->json(['message' => 'Permission created successfully!']);
    }

    /**
     * Show the form for editing the specified permission (AJAX).
     */
    public function edit($id)
    {
        $permission = Permission::findOrFail($id);

        return response()->json([
            'permission' => $permission,
        ]);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $id,
        ]);

        $permission = Permission::findOrFail($id);
        $permission->update(['name' => $request->name]);

        return response()->json(['message' => 'Permission updated successfully!']);
    }

    /**
     * Remove the specified permission.
     */
    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json(['message' => 'Permission deleted successfully!']);
    }

    /**
     * Bulk‐delete permissions.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'No permissions selected.'], 422);
        }

        Permission::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected permissions deleted successfully!']);
    }
}
