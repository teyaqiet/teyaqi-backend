<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions grouped by module/resource.
     */
    public function index()
    {
        $permissions = Permission::all()
            ->groupBy(function ($permission) {
                $parts = explode(':', $permission->name, 2);
                return isset($parts[1]) ? strtolower($parts[1]) : 'general';
            })
            ->sortKeys();

        return view('admin.permissions.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new permission.
     */
    public function create()
    {
        // Define standard CRUD and system actions for the select menu
        $actions = [
            'view_any'      => 'View Any (List)',
            'view'          => 'View (Details)',
            'create'        => 'Create',
            'edit'          => 'Edit / Update',
            'delete'        => 'Delete',
            'delete_any'    => 'Delete Any',
            'force_delete'  => 'Force Delete',
            'restore'       => 'Restore',
            'export'        => 'Export Data',
            'manage'        => 'Manage (Full Control)',
        ];

        return view('admin.permissions.create', compact('actions'));
    }

    /**
     * Store a newly created permission in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|max:100',
            'resource' => 'required|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
        ], [
            'resource.regex' => 'The resource module may only contain letters, numbers, dashes, and underscores.',
        ]);

        // Clean & format the combined string: "action:resource"
        $action = strtolower(trim($validated['action']));
        $resource = strtolower(trim($validated['resource']));
        $permissionName = "{$action}:{$resource}";

        // Validate uniqueness of the combined permission name
        if (Permission::where('name', $permissionName)->where('guard_name', 'web')->exists()) {
            return back()
                ->withInput()
                ->withErrors(['resource' => "The permission '{$permissionName}' already exists."]);
        }

        Permission::create([
            'name'       => $permissionName,
            'guard_name' => 'web',
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', "Permission '{$permissionName}' created successfully.");
    }

    /**
     * Show the form for editing the specified permission.
     */
    public function edit(Permission $permission)
    {
        return view('admin.permissions.edit', compact('permission'));
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update([
            'name' => strtolower($validated['name']),
        ]);

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return redirect()
            ->route('admin.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}