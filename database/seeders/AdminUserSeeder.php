<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('TEYAQI_ADMIN_EMAIL', 'admin@teyaqi.com');
        $password = env('TEYAQI_ADMIN_PASSWORD');

        if (blank($password)) {
            throw new RuntimeException(
                'TEYAQI_ADMIN_PASSWORD is not configured in the environment.'
            );
        }

        // Ensure Super Admin role exists for the admin guard.
        $superAdminRole = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'admin',
        ]);

        // Create the default admin only if it does not already exist.
        $admin = AdminUser::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make($password),
                'is_active' => true,
            ]
        );

        // Ensure the role is assigned.
        $admin->assignRole($superAdminRole);

        $this->command->info(
            "Default admin user ensured: {$email}"
        );
    }
}