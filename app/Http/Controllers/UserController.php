<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        $roles       = Role::query()->select('id','name')->orderBy('name')->get();
        $permissions = Permission::query()->select('id','name')->orderBy('name')->get();
        $modules     = Module::query()->select('id','name')->orderBy('name')->get();

        return view('users.index', compact('roles','permissions','modules'));
    }

    public function select2(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $users = User::query()
            ->when($q, function ($qry) use ($q) {
                $qry->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id','name','email']);

        return $users->map(function ($u) {
            return [
                'id'   => $u->id,
                'text' => trim(($u->name ?? 'User')
                        . ($u->email ? " ({$u->email})" : '')),
            ];
        });
    }
    /**
     * DataTable listing
     * Supports filters: role_id, module_id, status, global_search
     */
    public function list(Request $request)
    {
        // Keep it lightweight: load relations, but only the fields needed
        $query = User::query()
            ->select('users.*')
            ->with([
                'roles:id,name',
                'permissions:id,name',
                'modules:id,name',
            ]);

        // ---- Optional status filter (adjust to your schema) ----
        // If you have users.is_active (tinyint/bool)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('users.is_active', 1);
            } elseif ($request->status === 'inactive') {
                $query->where('users.is_active', 0);
            }
        }

        // Filter by role
        if ($request->filled('role_id')) {
            $roleId = (int) $request->role_id;
            $query->whereHas('roles', fn($q) => $q->where('roles.id', $roleId));
        }

        // Filter by module access
        if ($request->filled('module_id')) {
            $moduleId = (int) $request->module_id;
            $query->whereHas('modules', fn($q) => $q->where('modules.id', $moduleId));
        }

        // Global search (server-side) - matches name/email + role/perm/module names
        if ($request->filled('global_search')) {
            $term = trim($request->global_search);

            $query->where(function ($q) use ($term) {
                $q->where('users.name', 'like', "%{$term}%")
                  ->orWhere('users.email', 'like', "%{$term}%")
                  ->orWhereHas('roles', fn($r) => $r->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('permissions', fn($p) => $p->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('modules', fn($m) => $m->where('name', 'like', "%{$term}%"));
            });
        }

        return DataTables::of($query)
            ->addColumn('checkbox', fn($u) => '<input type="checkbox" class="user-check" name="ids[]" value="'.$u->id.'">')

            ->editColumn('name', fn($u) => e($u->name))
            ->editColumn('email', fn($u) => e($u->email))

            ->addColumn('roles', function ($u) {
                $names = $u->roles->pluck('name')->sort()->values()->all();
                return $this->chipsHtml($names, 3, 'bg-light text-dark');
            })

            ->addColumn('permissions', function ($u) {

                $perms  = $u->getAllPermissions()->pluck('name');
                $groups = $perms->groupBy(fn($p) => explode('.', $p)[0] ?: 'other');
            
                // show first 2 group chips
                $chips = $groups->take(2)->map(function ($items, $g) {
                    return '<span class="badge bg-info text-dark me-1">'.e($g).' ('.count($items).')</span>';
                })->implode(' ');
            
                $more = $groups->count() > 2
                    ? '<span class="badge bg-secondary">+'.($groups->count() - 2).' groups</span>'
                    : '';
            
                // REAL BUTTON (not link)
                $btn = '<button type="button" class="btn btn-sm btn-outline-primary mt-2 ms-2 view-perms"
                          data-id="'.$u->id.'">
                          <i class="fas fa-eye me-1"></i> View
                        </button>';
            
                return $chips.' '.$more.' '.$btn;
            })

            ->addColumn('access', function ($u) {
                $erp   = $u->can_access_erp ? '<span class="badge bg-success me-1">ERP</span>' : '<span class="badge bg-light text-muted me-1">ERP</span>';
                $admin = $u->can_access_admin ? '<span class="badge bg-dark me-1">Admin</span>' : '<span class="badge bg-light text-muted me-1">Admin</span>';
        
                // optional: show quick hint if neither
                if (!$u->can_access_erp && !$u->can_access_admin) {
                    return '<span class="text-muted">—</span>';
                }
        
                return $erp.' '.$admin;
            })

            ->addColumn('modules', function ($u) {
                $names = $u->modules->pluck('name')->sort()->values()->all();
                return $this->chipsHtml($names, 4, 'bg-light text-dark');
            })

            // Optional status column if you want to show it in table
            // ->addColumn('status', fn($u) => ($u->is_active ?? 1) ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>')

            ->addColumn('actions', function ($u) {
                return
                    '<button class="btn btn-warning btn-sm edit-btn" data-id="'.$u->id.'">Edit</button> '.
                    '<button class="btn btn-danger btn-sm delete-btn" data-id="'.$u->id.'">Delete</button>';
            })

            ->rawColumns(['checkbox','roles','permissions', 'access', 'modules','actions' /*,'status'*/])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:255'],
            'email'       => ['required','email','max:255', Rule::unique('users','email')],
            'password'    => ['required','string','min:6','confirmed'],
    
            'roles'       => ['nullable','array'],
            'roles.*'     => ['integer', Rule::exists('roles','id')],
    
            'permissions'   => ['nullable','array'],
            'permissions.*' => ['integer', Rule::exists('permissions','id')],
    
            'modules'     => ['nullable','array'],
            'modules.*'   => ['integer', Rule::exists('modules','id')],
    
            'can_access_erp'   => ['nullable','boolean'],
            'can_access_admin' => ['nullable','boolean'],
        ]);
    
        $data['can_access_erp']   = $request->boolean('can_access_erp');
        $data['can_access_admin'] = $request->boolean('can_access_admin');
    
        $user = User::create([
            'name'             => $data['name'],
            'email'            => $data['email'],
            'password'         => Hash::make($data['password']),
            'can_access_erp'   => $data['can_access_erp'],
            'can_access_admin' => $data['can_access_admin'],
        ]);
    
        $roleIds   = $request->input('roles', []);
        $permIds   = $request->input('permissions', []);
        $moduleIds = $request->input('modules', []);
    
        $roleNames = Role::whereIn('id', $roleIds)->pluck('name')->all();
        $permNames = Permission::whereIn('id', $permIds)->pluck('name')->all();
    
        $user->syncRoles($roleNames);
        $user->syncPermissions($permNames);
        $user->modules()->sync($moduleIds);
    
        // (optional) load for audit snapshot
        $user->load(['modules:id,name']);
    
        // audit: created
        $this->audit(
            action: 'created',
            description: 'Created user '.$user->name.' ('.$user->email.')',
            subject: $user,
            meta: [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'can_access_admin' => $user->can_access_admin ? 'yes' : 'no',
                'can_access_erp'   => $user->can_access_erp ? 'yes' : 'no',
                'roles'            => $roleNames,
                'permissions'      => $permNames,
                'modules'          => $user->modules->pluck('name')->sort()->values()->all(),
            ]
        );
    
        return response()->json(['message' => 'User created successfully!']);
    }

    
    public function edit($id)
    {
        $user = User::with(['roles:id','permissions:id','modules:id'])->findOrFail($id);
    
        return response()->json([
            'user' => $user->only('id','name','email','can_access_erp','can_access_admin'),
            'userRoleIds'   => $user->roles->pluck('id')->values(),
            'userPermIds'   => $user->permissions->pluck('id')->values(),
            'userModuleIds' => $user->modules->pluck('id')->values(),
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $user = User::with(['roles:id,name', 'permissions:id,name', 'modules:id,name'])->findOrFail($id);
    
        // BEFORE snapshot
        $before = [
            'id'               => $user->id,
            'name'             => $user->name,
            'email'            => $user->email,
            'can_access_erp'   => (bool) $user->can_access_erp,
            'can_access_admin' => (bool) $user->can_access_admin,
            'roles'            => $user->roles->pluck('name')->sort()->values()->all(),
            'permissions'      => $user->permissions->pluck('name')->sort()->values()->all(),
            'modules'          => $user->modules->pluck('name')->sort()->values()->all(),
        ];
    
        $data = $request->validate([
            'name'        => ['required','string','max:255'],
            'email'       => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
    
            'roles'       => ['nullable','array'],
            'roles.*'     => ['integer', Rule::exists('roles','id')],
    
            'permissions'   => ['nullable','array'],
            'permissions.*' => ['integer', Rule::exists('permissions','id')],
    
            'modules'     => ['nullable','array'],
            'modules.*'   => ['integer', Rule::exists('modules','id')],
    
            'can_access_erp'   => ['nullable','boolean'],
            'can_access_admin' => ['nullable','boolean'],
        ]);
    
        // normalize booleans
        $data['can_access_erp']   = $request->boolean('can_access_erp');
        $data['can_access_admin'] = $request->boolean('can_access_admin');
    
        // update core fields
        $user->update([
            'name'             => $data['name'],
            'email'            => $data['email'],
            'can_access_erp'   => $data['can_access_erp'],
            'can_access_admin' => $data['can_access_admin'],
        ]);
    
        // sync relationships
        $roleIds   = $request->input('roles', []);
        $permIds   = $request->input('permissions', []);
        $moduleIds = $request->input('modules', []);
    
        $roleNames = Role::whereIn('id', $roleIds)->pluck('name')->all();
        $permNames = Permission::whereIn('id', $permIds)->pluck('name')->all();
    
        $user->syncRoles($roleNames);
        $user->syncPermissions($permNames);
        $user->modules()->sync($moduleIds);
    
        // AFTER snapshot
        $user->load(['roles:id,name', 'permissions:id,name', 'modules:id,name']);
    
        $after = [
            'id'               => $user->id,
            'name'             => $user->name,
            'email'            => $user->email,
            'can_access_erp'   => (bool) $user->can_access_erp,
            'can_access_admin' => (bool) $user->can_access_admin,
            'roles'            => $user->roles->pluck('name')->sort()->values()->all(),
            'permissions'      => $user->permissions->pluck('name')->sort()->values()->all(),
            'modules'          => $user->modules->pluck('name')->sort()->values()->all(),
        ];
    
        // DIFF (what changed)
        $changes = [];
    
        foreach (['name','email','can_access_erp','can_access_admin'] as $k) {
            if (($before[$k] ?? null) !== ($after[$k] ?? null)) {
                $changes[$k] = ['from' => $before[$k] ?? null, 'to' => $after[$k] ?? null];
            }
        }
    
        $changes['roles'] = [
            'added'   => array_values(array_diff($after['roles'], $before['roles'])),
            'removed' => array_values(array_diff($before['roles'], $after['roles'])),
        ];
        $changes['permissions'] = [
            'added'   => array_values(array_diff($after['permissions'], $before['permissions'])),
            'removed' => array_values(array_diff($before['permissions'], $after['permissions'])),
        ];
        $changes['modules'] = [
            'added'   => array_values(array_diff($after['modules'], $before['modules'])),
            'removed' => array_values(array_diff($before['modules'], $after['modules'])),
        ];
    
        // (Optional) if nothing changed, keep it clean
        $hasAnyChange =
            count($changes) > 0 &&
            (
                isset($changes['name']) || isset($changes['email']) ||
                isset($changes['can_access_erp']) || isset($changes['can_access_admin']) ||
                !empty($changes['roles']['added']) || !empty($changes['roles']['removed']) ||
                !empty($changes['permissions']['added']) || !empty($changes['permissions']['removed']) ||
                !empty($changes['modules']['added']) || !empty($changes['modules']['removed'])
            );
    
        $this->audit(
            action: 'updated',
            description: 'Updated user '.$user->name.' ('.$user->email.')',
            subject: $user,
            meta: [
                'before'  => $before,
                'after'   => $after,
                'changes' => $hasAnyChange ? $changes : [],
            ]
        );
    
        return response()->json(['message' => 'User updated successfully!']);
    }


    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'User deleted successfully!']);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required','array','min:1'],
            'ids.*' => ['integer', Rule::exists('users','id')],
        ]);

        User::whereIn('id', $data['ids'])->delete();

        return response()->json(['message' => 'Selected users deleted successfully!']);
    }

    // ------------------------------------------------------------------
    // HTML helpers for readable DataTable output
    // ------------------------------------------------------------------

    private function chipsHtml(array $items, int $limit = 4, string $badgeClass = 'bg-light text-dark'): string
    {
        $items = array_values(array_filter($items));
        $total = count($items);

        if ($total === 0) return '<span class="text-muted">—</span>';

        $shown = array_slice($items, 0, $limit);
        $extra = $total - count($shown);

        $html = '';
        foreach ($shown as $name) {
            $html .= '<span class="badge '.$badgeClass.' me-1 mb-1">'.e($name).'</span>';
        }

        if ($extra > 0) {
            $html .= '<span class="badge bg-secondary me-1 mb-1">+'.$extra.' more</span>';
        }

        return $html;
    }

    /**
     * Group permissions by prefix (before first dot), show a compact summary:
     * - Shows up to $groupLimit groups, each group shows up to $perGroupLimit items in tooltip.
     * - Great for DataTable readability.
     */
    private function groupedPermissionsHtml(array $permissionNames, int $groupLimit = 3, int $perGroupLimit = 10): string
    {
        $permissionNames = array_values(array_filter($permissionNames));
        if (count($permissionNames) === 0) return '<span class="text-muted">—</span>';

        // Group by first segment (inventory.*, crm.*, etc.)
        $groups = [];
        foreach ($permissionNames as $p) {
            $seg = explode('.', $p)[0] ?? 'other';
            $seg = $seg ?: 'other';
            $groups[$seg][] = $p;
        }

        ksort($groups);

        $totalGroups = count($groups);
        $shownGroups = array_slice($groups, 0, $groupLimit, true);
        $extraGroups = $totalGroups - count($shownGroups);

        $html = '<div class="d-flex flex-wrap">';

        foreach ($shownGroups as $group => $perms) {
            sort($perms);
            $count = count($perms);

            // Tooltip is optional; keep it lightweight
            $tooltipList = array_slice($perms, 0, $perGroupLimit);
            $tooltipExtra = $count - count($tooltipList);

            $tooltip = e(implode("\n", $tooltipList) . ($tooltipExtra > 0 ? "\n+{$tooltipExtra} more..." : ''));

            $html .= '<span class="badge bg-info text-dark me-1 mb-1" title="'.$tooltip.'">'
                   . e($group) . ' (' . $count . ')</span>';
        }

        if ($extraGroups > 0) {
            $html .= '<span class="badge bg-secondary me-1 mb-1">+'.$extraGroups.' groups</span>';
        }

        $html .= '</div>';

        return $html;
    }
    
    public function permissionsDetails($id)
    {
        $user = User::with(['permissions', 'roles.permissions'])->findOrFail($id);
    
        // effective permissions (direct + via roles)
        $perms = $user->getAllPermissions()->pluck('name');
    
        $grouped = $perms->groupBy(fn($p) => explode('.', $p)[0] ?: 'other')
                         ->map(fn($items) => $items->values());
    
        return response()->json([
            'user' => $user->only('id','name','email'),
            'grouped' => $grouped,
            'total' => $perms->count(),
        ]);
    }
    
    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        // module label for this controller
        $module = 'inventory.stock_entries';
    
        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

}
