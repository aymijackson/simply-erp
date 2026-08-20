<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 2026_08_20_100001 granted the production module to roles named 'admin' and 'manager',
 * but the real roles are 'Administrator' and 'Super Admin' - that migration found no
 * matching roles and silently did nothing. This targets the real names instead.
 */
return new class extends Migration
{
    protected array $roleNames = ['Administrator', 'Super Admin'];

    public function up(): void
    {
        if (!Schema::hasTable('role_has_modules') || !Schema::hasTable('modules') || !Schema::hasTable('roles')) {
            return;
        }

        $moduleId = DB::table('modules')->where('slug', 'production')->value('id');
        if (!$moduleId) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('name', $this->roleNames)->pluck('id');

        foreach ($roleIds as $roleId) {
            $exists = DB::table('role_has_modules')
                ->where('role_id', $roleId)
                ->where('module_id', $moduleId)
                ->exists();

            if (!$exists) {
                DB::table('role_has_modules')->insert([
                    'role_id' => $roleId,
                    'module_id' => $moduleId,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('role_has_modules') || !Schema::hasTable('modules')) {
            return;
        }

        $moduleId = DB::table('modules')->where('slug', 'production')->value('id');
        if (!$moduleId) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('name', $this->roleNames)->pluck('id');

        DB::table('role_has_modules')
            ->where('module_id', $moduleId)
            ->whereIn('role_id', $roleIds)
            ->delete();
    }
};
