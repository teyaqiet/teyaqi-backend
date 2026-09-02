<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
        require base_path('routes/operations.php');
    },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // --- 1. STOP THE 419 ERROR ---
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // --- 2. REGISTER YOUR CUSTOM MIDDLEWARE ---
        $middleware->alias([
            'ensure_onboarded' => \App\Http\Middleware\EnsureOnboarded::class,
            'log.api' => \App\Http\Middleware\LogApiRequests::class,
        ]);
        
        

        // --- 3. GUEST REDIRECTS (Handles Web, Admin Panel & API) ---
        $middleware->redirectGuestsTo(function (Request $request) {
            // API requests return 401 JSON instead of redirecting
            if ($request->expectsJson() || $request->is('api/*')) {
                return null; // Will trigger 401 Unauthenticated JSON response
            }

            // Unauthenticated admin requests go to the admin login page
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            // Fallback for general web requests
            return route('login');
        });

        // --- 4. REST OF YOUR SETTINGS ---
        $middleware->statefulApi();
        $middleware->trustProxies(at: '*');
    })

    ->withSchedule(function (Schedule $schedule) {
        // Run your difficulty calibration every night at midnight
        $schedule->command('quiz:calibrate-difficulty')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();