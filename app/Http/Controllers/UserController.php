<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        $roles       = Role::all(['id','name']);
        $permissions = Permission::all(['id','name']);
        $modules     = Module::all(['id','name']);

        return view('users.index', compact('roles','permissions','modules'));
    }

    public function list(Request $request)
    {
        $query = User::with(['roles','permissions','modules'])->select('users.*');

        return DataTables::of($query)
            ->addColumn('checkbox', fn($u) => '<input type="checkbox" name="ids[]" value="'.$u->id.'">')
            ->addColumn('roles', fn($u) => $u->getRoleNames()->implode(', '))
            ->addColumn('permissions', fn($u) => $u->getPermissionNames()->implode(', '))
            ->addColumn('modules', fn($u) => $u->modules->pluck('name')->implode(', '))
            ->addColumn('actions', fn($u) =>
                  '<button class="btn btn-warning btn-sm edit-btn" data-id="'.$u->id.'">Edit</button> '.
                  '<button class="btn btn-danger btn-sm delete-btn" data-id="'.$u->id.'">Delete</button>')
            ->rawColumns(['checkbox','actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:6|confirmed',
            'roles'       => 'nullable|array',
            'permissions' => 'nullable|array',
            'modules'     => 'nullable|array',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // map role IDs → names
        $roleNames = Role::whereIn('id', $request->input('roles', []))
                         ->pluck('name')
                         ->toArray();
        $user->syncRoles($roleNames);

        // map permission IDs → names
        $permNames = Permission::whereIn('id', $request->input('permissions', []))
                               ->pluck('name')
                               ->toArray();
        $user->syncPermissions($permNames);

        // modules by ID
        $user->modules()->sync($request->input('modules', []));

        return response()->json(['message' => 'User created successfully!']);
    }

    public function edit($id)
    {
        $user = User::with(['roles','permissions','modules'])->findOrFail($id);

        return response()->json([
            'user'           => $user->only('id','name','email'),
            'userRoleIds'    => $user->roles->pluck('id'),
            'userPermIds'    => $user->permissions->pluck('id'),
            'userModuleIds'  => $user->modules->pluck('id'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,'.$id,
            'roles'       => 'nullable|array',
            'permissions' => 'nullable|array',
            'modules'     => 'nullable|array',
        ]);

        $user = User::findOrFail($id);
        $user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        // map role IDs → names
        $roleNames = Role::whereIn('id', $request->input('roles', []))
                         ->pluck('name')
                         ->toArray();
        $user->syncRoles($roleNames);

        // map permission IDs → names
        $permNames = Permission::whereIn('id', $request->input('permissions', []))
                               ->pluck('name')
                               ->toArray();
        $user->syncPermissions($permNames);

        // modules by ID
        $user->modules()->sync($request->input('modules', []));

        return response()->json(['message' => 'User updated successfully!']);
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return response()->json(['message' => 'User deleted successfully!']);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['message' => 'No users selected!'], 422);
        }

        User::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected users deleted successfully!']);
    }
}
