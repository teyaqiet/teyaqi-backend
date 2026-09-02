<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure the default Super Admin role exists for the admin guard
        $superAdminRole = Role::firstOrCreate([
            'name'       => 'Super Admin',
            'guard_name' => 'admin',
        ]);

        // 2. Create the default Super Admin user
        $admin = AdminUser::firstOrCreate(
            ['email' => 'admin@teyaqi.com'],
            [
                'name'      => 'Super Admin',
                'password'  => Hash::make('Password123!'), // Change this to a secure password
                'is_active' => true,
            ]
        );

        // 3. Assign the Super Admin role to the user
        $admin->assignRole($superAdminRole);

        $this->command->info('Default Admin User created successfully: admin@teyaqi.com / Password123!');
    }
}