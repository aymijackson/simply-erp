<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The sidebar gates the whole "Manufacturing/Production" section behind
 * canAccessModule('production'), which checks role_has_modules independently of the
 * production.* Spatie permissions - so even with those permissions correctly synced to
 * a role, the menu section stays hidden unless the role also has a role_has_modules row
 * for the production module. Production is one of the newer modules and was apparently
 * never wired into that table when it was set up for the others.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('role_has_modules') || !Schema::hasTable('modules') || !Schema::hasTable('roles')) {
            return;
        }

        $moduleId = DB::table('modules')->where('slug', 'production')->value('id');
        if (!$moduleId) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('name', ['admin', 'manager'])->pluck('id');

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

        $roleIds = DB::table('roles')->whereIn('name', ['admin', 'manager'])->pluck('id');

        DB::table('role_has_modules')
            ->where('module_id', $moduleId)
            ->whereIn('role_id', $roleIds)
            ->delete();
    }
};
