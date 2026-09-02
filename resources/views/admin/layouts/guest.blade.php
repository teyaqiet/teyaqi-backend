@props(['title' => null])
@php 
    $title = $title ?? config('app.name', 'Teyaqi') . ' — Admin Portal'; 
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title }}</title>
    
    <!-- Favicon & Icons -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/Teyaqi-logo.svg') }}" />
    <link rel="alternate icon" href="{{ asset('/img/Teyaqi-favicon.svg') }}" />
    
    <!-- Fonts & IconKit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('plugins/icon-kit/dist/css/iconkit.min.css') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-[#4a5361] antialiased selection:bg-indigo-500 selection:text-white">
    <div class="flex min-h-screen flex-col items-center justify-center p-4 sm:p-6">
        
        <!-- Brand / App Logo Header -->
        <div class="mb-6 text-center">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-3 transition hover:opacity-90">
                <img src="{{ asset('img/Teyaqi-logo.svg') }}" alt="Teyaqi Logo" class="h-10 w-auto" />
                <span class="text-2xl font-extrabold tracking-tight text-slate-800">
                    {{ config('app.name', 'Teyaqi') }}
                </span>
            </a>
        </div>

        <!-- Auth Card Container -->
        <div class="w-full max-w-md">
            <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-xl shadow-black/5">
                {{ $slot }}
            </div>
            
            <!-- Footer Links -->
            <div class="mt-6 text-center text-xs text-gray-400">
                &copy; {{ date('Y') }} {{ config('app.name', 'Teyaqi') }}. All rights reserved.
            </div>
        </div>

    </div>
</body>
</html>