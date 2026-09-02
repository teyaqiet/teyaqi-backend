<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Services\ActivityLogger;

class AdminUserController extends Controller
{
    public function index()
    {
        // Querying AdminUser directly from admin_users table
        $users = AdminUser::with('roles')
            ->latest()
            ->paginate(15);

        return view('admin.admin-users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::where('guard_name', 'admin')->get();

        return view('admin.admin-users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:admin_users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role'     => ['required', 'exists:roles,name'],
        ]);

        $admin = AdminUser::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active'=> true,
        ]);

        $admin->assignRole($data['role']);

        // Log activity
        ActivityLogger::log('created', 'Created new admin user: ' . $admin->name, $admin);

        return redirect()
            ->route('admin.admin-users.index')
            ->with('success', 'Admin user created successfully.');
    }

    public function edit(AdminUser $admin_user)
    {
        $roles = Role::where('guard_name', 'admin')->get();

        return view('admin.admin-users.edit', [
            'user'  => $admin_user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, AdminUser $admin_user)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admin_users')->ignore($admin_user->id)],
            'role'  => ['required', 'exists:roles,name'],
        ]);

        $admin_user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        $admin_user->syncRoles([$data['role']]);

        // Log activity
        ActivityLogger::log('updated', 'Updated admin user: ' . $admin_user->name, $admin_user);

        return redirect()
            ->route('admin.admin-users.index')
            ->with('success', 'Admin user updated successfully.');
    }

    public function destroy(AdminUser $admin_user)
    {
        $name = $admin_user->name;

        // Delete the admin record completely from admin_users table
        $admin_user->delete();

        // Log activity
        ActivityLogger::log('deleted', 'Deleted admin user: ' . $name);

        return back()->with('success', 'Admin user deleted successfully.');
    }

    public function toggleStatus(AdminUser $admin_user)
    {
        $admin_user->update([
            'is_active' => ! $admin_user->is_active,
        ]);

        // Log activity
        ActivityLogger::log('updated', 'Toggled status for admin user: ' . $admin_user->name, $admin_user);

        return back()->with('success', 'Admin user status updated successfully.');
    }

    public function profile()
    {
        $user = auth()->user();

        return view('admin.admin-users.profile.index', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('admin_users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'phone'    => ['nullable', 'string', 'max:50'],
            'address'  => ['nullable', 'string', 'max:500'],
            'location' => ['nullable', 'string', 'max:100'],
        ]);

        $updateData = [
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? $user->phone,
            'address'  => $data['address'] ?? $user->address,
            'location' => $data['location'] ?? $user->location,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        // Log activity
        ActivityLogger::log('updated', 'Updated their own profile settings.', $user);

        return redirect()
            ->route('admin.profile.index')
            ->with('status', 'Profile updated successfully.');
    }
}