<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index()
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->paginate(10);

        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = $this->getGroupedPermissions();

        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users' => function ($query) {
            $query->latest()->limit(50);
        }])->loadCount('users');

        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = $this->getGroupedPermissions();

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update([
            'name' => $validated['name'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting critical system roles if necessary
        if ($role->name === 'Super Admin') {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', 'The Super Admin role cannot be deleted.');
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Helper to structure permissions into a resource-action matrix.
     */
    protected function getGroupedPermissions()
    {
        $allPermissions = Permission::all();
        $matrix = [];

        foreach ($allPermissions as $permission) {
            // Splitting "action:resource" (e.g., "view_any:users" -> action: view_any, resource: users)
            $parts = explode(':', $permission->name, 2);

            $rawAction = strtolower($parts[0] ?? 'access');
            $resource  = strtolower($parts[1] ?? 'general');

            // Map common variations or camelCase/kebab-case names to standard snake_case keys
            $action = match ($rawAction) {
                'view-own', 'viewown'              => 'view',
                'view-any', 'viewany'              => 'view_any',
                'delete-own', 'deleteown'          => 'delete',
                'delete-any', 'deleteany'          => 'delete_any',
                'force-delete', 'forcedelete'      => 'force_delete',
                'force-delete-own'                 => 'force_delete',
                'force-delete-any', 'forcedeleteany'=> 'force_delete_any',
                'restore-any', 'restoreany'        => 'restore_any',
                default                            => str_replace('-', '_', $rawAction),
            };

            $matrix[$resource][$action] = $permission;
        }

        return $matrix;
    }
}