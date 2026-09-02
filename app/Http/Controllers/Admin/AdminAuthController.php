<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLogger;
use App\Models\AdminUser;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Attempt login using the admin guard
        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $admin = Auth::guard('admin')->user();

            // Ensure deactivated admins cannot log in
            if (! $admin->is_active) {
                ActivityLogger::log('login_failed', 'Failed login attempt (Account Deactivated): ' . $credentials['email']);
                
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Your account has been deactivated.',
                ]);
            }

            // Log successful login
            ActivityLogger::log('login', 'Admin logged in successfully: ' . $admin->name, $admin);

            return redirect()->intended(route('admin.dashboard'));
        }

        // Log failed login attempt
        ActivityLogger::log('login_failed', 'Failed login attempt with email: ' . $credentials['email']);

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if ($admin) {
            ActivityLogger::log('logout', 'Admin logged out: ' . $admin->name, $admin);
        }

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}