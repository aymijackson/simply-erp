<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(SettingsSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'aymi247@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => \Hash::make('password'),
                'can_access_erp' => true,
                'can_access_admin' => true,
            ]
        );

        $admin->assignRole('admin');
    }
}
