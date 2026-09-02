<x-auth-layout title="Admin Login — Teyaqi">

    <div class="mb-6 text-center">
        <h1 class="text-xl font-bold text-gray-900">{{ __('Welcome back') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('Please sign in to your admin account') }}</p>
    </div>

    <!-- Session Status / Flash Alerts -->
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 p-3 text-xs font-medium text-emerald-600 border border-emerald-100">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                {{ __('Email Address') }}
            </label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <i class="ik ik-mail"></i>
                </span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full rounded-xl border @error('email') border-rose-300 bg-rose-50/30 @else border-gray-200 @enderror py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    placeholder="admin@teyaqi.com">
            </div>
            @error('email')
                <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                    {{ __('Password') }}
                </label>
                @if (Route::has('admin.password.request'))
                    <a href="{{ route('admin.password.request') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <i class="ik ik-lock"></i>
                </span>
                <input id="password" type="password" name="password" required
                    class="w-full rounded-xl border @error('password') border-rose-300 bg-rose-50/30 @else border-gray-200 @enderror py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                    placeholder="••••••••">
            </div>
            @error('password')
                <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span class="text-xs text-gray-600">{{ __('Remember me on this device') }}</span>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
            <button type="submit" 
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 py-2.5 px-4 text-sm font-semibold text-white transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-indigo-700 shadow-lg shadow-indigo-600/20">
                <span>{{ __('Sign In') }}</span>
                <i class="ik ik-arrow-right"></i>
            </button>
        </div>
    </form>

</x-auth-layout>