<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Standard CRUD actions to generate for each model module.
     */
    protected array $actions = [
        'view_any',
        'view',
        'create',
        'edit',
        'delete',
        'force_delete',
        'restore',
    ];

    /**
     * Additional standalone or module-specific permissions.
     */
    protected array $customPermissions = [
        'manage:settings',
        'view_any:analytics',
        'export:reports',
        'view_any:audit_logs',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Discover all resources from app/Models
        $resources = $this->discoverModelResources();

        // 2. Build permission list
        $permissionsToCreate = [];

        foreach ($resources as $resource) {
            foreach ($this->actions as $action) {
                $permissionsToCreate[] = "{$action}:{$resource}";
            }
        }

        // Merge custom non-CRUD permissions
        $allPermissions = array_unique(array_merge($permissionsToCreate, $this->customPermissions));

        // 3. Upsert permissions into the database
        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name'       => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // 4. Assign all permissions to Super Admin role automatically
        $superAdminRole = Role::firstOrCreate([
            'name'       => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $superAdminRole->syncPermissions(Permission::all());
    }

    /**
     * Scan app/Models directory and return pluralized snake_case resource names.
     */
    protected function discoverModelResources(): array
    {
        $modelPath = app_path('Models');

        if (!File::exists($modelPath)) {
            return [];
        }

        $files = File::allFiles($modelPath);
        $resources = [];

        foreach ($files as $file) {
            // Convert path to class name (handles nested folders like App\Models\Game\Session)
            $relativePath = $file->getRelativePathname();
            $className = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

            if (class_exists($className)) {
                $classBasename = class_basename($className);
                
                // Convert Model name (e.g., PlayerMetric -> player_metrics)
                $resources[] = Str::snake(Str::pluralStudly($classBasename));
            }
        }

        return array_unique($resources);
    }
}