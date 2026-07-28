<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

if (! function_exists('canAccessModule')) {
    function canAccessModule(string $moduleSlug): bool
    {
        $user = Auth::user();
        if (! $user) return false;

        // If Super Admin should see everything (optional)
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        // role_has_modules table: role_id, module_id
        return DB::table('role_has_modules')
            ->join('modules', 'modules.id', '=', 'role_has_modules.module_id')
            ->where('modules.slug', $moduleSlug)
            ->whereIn('role_has_modules.role_id', $user->roles()->pluck('id'))
            ->exists();
    }
}

if (! function_exists('audit_log')) {
    function audit_log(string $module, string $action, ?string $description = null, ?Model $subject = null, array $meta = []): ?AuditLog
    {
        $user = auth()->user();

        if ($user) {
            return $user->audit($module, $action, $description, $subject, $meta);
        }

        // optional: if you want guest logs too
        return AuditLog::create([
            'user_id'      => null,
            'module'       => $module,
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'route'        => optional(request()->route())->getName(),
            'url'          => request()->fullUrl(),
            'method'       => request()->method(),
            'ip'           => request()->ip(),
            'user_agent'   => substr((string) request()->userAgent(), 0, 500),
            'meta'         => $meta,
        ]);
    }
}
