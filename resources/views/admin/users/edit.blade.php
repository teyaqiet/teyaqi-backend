@extends('admin.layouts.main')

@section('title', 'Edit User')

@section('content')

<div class="space-y-6">


{{-- ================================================================
     HEADER
================================================================= --}}

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

    <div class="flex items-center gap-4">

        <a
            href="{{ route('admin.users.show', $user) }}"
            class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50"
        >
            <i class="ik ik-arrow-left"></i>
        </a>

        <div>

            <h1 class="text-2xl font-bold text-gray-800">
                Edit User
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Update player profile and gameplay information.
            </p>

        </div>

    </div>

</div>


{{-- ================================================================
     VALIDATION ERRORS
================================================================= --}}

@if($errors->any())

    <div class="rounded-xl border border-red-100 bg-red-50 p-4">

        <div class="flex gap-3">

            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
                <i class="ik ik-alert-circle"></i>
            </div>

            <div>

                <p class="text-sm font-semibold text-red-700">
                    Please fix the following errors
                </p>

                <ul class="mt-2 space-y-1 text-xs text-red-600">

                    @foreach($errors->all() as $error)

                        <li>
                            • {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        </div>

    </div>

@endif


<form
    method="POST"
    action="{{ route('admin.users.update', $user) }}"
    class="space-y-6"
>

    @csrf
    @method('PUT')


    {{-- ============================================================
         ACCOUNT INFORMATION
    ============================================================= --}}

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

        <div class="mb-6">

            <h2 class="font-semibold text-gray-800">
                Account Information
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Personal information and account credentials.
            </p>

        </div>


        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">


            {{-- NAME --}}

            <div>

                <label
                    for="name"
                    class="block text-xs font-medium text-gray-600"
                >
                    Full Name
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name', $user->name) }}"
                    required
                    class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 @error('name') border-red-400 @enderror"
                >

                @error('name')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

            </div>


            {{-- USERNAME --}}

            <div>

                <label
                    for="username"
                    class="block text-xs font-medium text-gray-600"
                >
                    Username
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="username"
                    id="username"
                    value="{{ old('username', $user->username) }}"
                    required
                    class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 @error('username') border-red-400 @enderror"
                >

                @error('username')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

            </div>


            {{-- EMAIL --}}

            <div>

                <label
                    for="email"
                    class="block text-xs font-medium text-gray-600"
                >
                    Email Address
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email', $user->email) }}"
                    required
                    class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 @error('email') border-red-400 @enderror"
                >

                @error('email')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

            </div>


            {{-- LANGUAGE --}}

            <div>

                <label
                    for="language"
                    class="block text-xs font-medium text-gray-600"
                >
                    Language
                </label>

                <select
                    name="language"
                    id="language"
                    class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                >

                    <option
                        value="am"
                        @selected(old('language', $user->language) === 'am')
                    >
                        Amharic (አማርኛ)
                    </option>

                    <option
                        value="en"
                        @selected(old('language', $user->language) === 'en')
                    >
                        English
                    </option>

                </select>

            </div>


            {{-- GENDER --}}

            <div>

                <label
                    for="gender"
                    class="block text-xs font-medium text-gray-600"
                >
                    Gender
                </label>

                <select
                    name="gender"
                    id="gender"
                    class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                >

                    <option value="">
                        Unspecified
                    </option>

                    <option
                        value="male"
                        @selected(old('gender', $user->gender) === 'male')
                    >
                        Male
                    </option>

                    <option
                        value="female"
                        @selected(old('gender', $user->gender) === 'female')
                    >
                        Female
                    </option>

                </select>

            </div>

        </div>

    </div>


    {{-- ============================================================
         GAMEPLAY
    ============================================================= --}}

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

        <div class="mb-6">

            <h2 class="font-semibold text-gray-800">
                Gameplay
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Manage the player's current progression and game state.
            </p>

        </div>


        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">


            {{-- TOTAL XP --}}

            <div>

                <label
                    for="total_xp"
                    class="block text-xs font-medium text-gray-600"
                >
                    Total XP
                </label>

                <input
                    type="number"
                    name="total_xp"
                    id="total_xp"
                    min="0"
                    value="{{ old('total_xp', $user->total_xp) }}"
                    class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                >

            </div>


            {{-- COINS --}}

            <div>

                <label
                    for="total_coins"
                    class="block text-xs font-medium text-gray-600"
                >
                    Total Coins
                </label>

                <input
                    type="number"
                    name="total_coins"
                    id="total_coins"
                    min="0"
                    value="{{ old('total_coins', $user->total_coins) }}"
                    class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                >

            </div>


            {{-- CURRENT SR --}}

            <div>

                <label
                    for="current_sr"
                    class="block text-xs font-medium text-gray-600"
                >
                    Current SR
                </label>

                <input
                    type="number"
                    step="0.01"
                    name="current_sr"
                    id="current_sr"
                    value="{{ old('current_sr', $user->current_sr) }}"
                    class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                >

            </div>


            {{-- DAILY LIVES --}}

            <div>

                <label
                    for="daily_lives"
                    class="block text-xs font-medium text-gray-600"
                >
                    Daily Lives
                </label>

                <input
                    type="number"
                    name="daily_lives"
                    id="daily_lives"
                    min="0"
                    value="{{ old('daily_lives', $user->daily_lives) }}"
                    class="mt-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10"
                >

            </div>

        </div>

    </div>


    {{-- ============================================================
         ACCOUNT STATE
    ============================================================= --}}

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">

        <div class="mb-6">

            <h2 class="font-semibold text-gray-800">
                Account State
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Control the player's onboarding state.
            </p>

        </div>


        <label class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 transition hover:bg-gray-100">

            <div>

                <p class="text-sm font-medium text-gray-700">
                    Completed Onboarding
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    Player will skip the initial setup screens.
                </p>

            </div>


            <div class="relative">

                <input
                    type="checkbox"
                    name="has_onboarded"
                    value="1"
                    class="peer sr-only"
                    @checked(old('has_onboarded', $user->has_onboarded))
                >

                <div class="h-6 w-11 rounded-full bg-gray-200 transition peer-checked:bg-indigo-600"></div>

                <div class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white shadow transition peer-checked:translate-x-5"></div>

            </div>

        </label>

    </div>


    {{-- ============================================================
         ACTIONS
    ============================================================= --}}

    <div class="flex items-center justify-between">

        <a
            href="{{ route('admin.users.show', $user) }}"
            class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
        >
            Cancel
        </a>


        <button
            type="submit"
            class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white transition hover:bg-indigo-700"
        >
            Save Changes
        </button>

    </div>

</form>


</div>

@endsection
