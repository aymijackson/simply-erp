<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $managerRole = Role::create(['name' => 'manager']);
        $userRole = Role::create(['name' => 'user']);

        // Create permissions
        $viewReports = Permission::create(['name' => 'view reports']);
        $editReports = Permission::create(['name' => 'edit reports']);
        $deleteReports = Permission::create(['name' => 'delete reports']);

        // Assign permissions to roles
        $adminRole->givePermissionTo([$viewReports, $editReports, $deleteReports]);
        $managerRole->givePermissionTo([$viewReports, $editReports]);
        $userRole->givePermissionTo($viewReports);
    }
}