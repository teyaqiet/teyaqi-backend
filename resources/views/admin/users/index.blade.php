@extends('admin.layouts.main')

@section('title', 'Users')

@section('content')

<div class="space-y-6">

    {{-- =========================================================
        FLASH SUCCESS
    ========================================================== --}}

    @if(session('success'))

        <div class="flex items-center justify-between rounded-xl bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20">

            <div class="flex items-center gap-2">

                <i class="ik ik-check-circle text-lg"></i>

                <span>
                    {{ session('success') }}
                </span>

            </div>

            <button
                type="button"
                onclick="this.parentElement.remove()"
                class="text-emerald-600 hover:text-emerald-900"
            >
                &times;
            </button>

        </div>

    @endif


    {{-- =========================================================
        FLASH ERROR
    ========================================================== --}}

    @if(session('error'))

        <div class="flex items-center justify-between rounded-xl bg-rose-50 p-4 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-600/20">

            <div class="flex items-center gap-2">

                <i class="ik ik-alert-circle text-lg"></i>

                <span>
                    {{ session('error') }}
                </span>

            </div>

            <button
                type="button"
                onclick="this.parentElement.remove()"
                class="text-rose-600 hover:text-rose-900"
            >
                &times;
            </button>

        </div>

    @endif


    {{-- =========================================================
        HEADER
    ========================================================== --}}

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <h1 class="text-2xl font-bold text-gray-800">
                Users
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Manage and monitor Teyaqi players.
            </p>

        </div>


        <div class="flex items-center gap-3">

            <span class="rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-500">

                {{ number_format($users->total()) }}

                {{ Str::plural('user', $users->total()) }}

            </span>

        </div>

    </div>


    {{-- =========================================================
        FILTERS
    ========================================================== --}}

    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

        <form
            method="GET"
            action="{{ route('admin.users.index') }}"
        >

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">


                {{-- SEARCH --}}

                <div class="lg:col-span-2">

                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Name, username, Telegram ID..."
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                </div>


                {{-- LANGUAGE --}}

                <div>

                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        Language
                    </label>

                    <select
                        name="language"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            All languages
                        </option>

                        @foreach($languages as $language)

                            <option
                                value="{{ $language }}"
                                @selected(request('language') === $language)
                            >
                                {{ strtoupper($language) }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- GENDER --}}

                <div>

                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        Gender
                    </label>

                    <select
                        name="gender"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            All genders
                        </option>

                        @foreach($genders as $gender)

                            <option
                                value="{{ $gender }}"
                                @selected(request('gender') === $gender)
                            >
                                {{ ucfirst($gender) }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- ONBOARDING --}}

                <div>

                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        Onboarding
                    </label>

                    <select
                        name="onboarded"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    >

                        <option value="">
                            Everyone
                        </option>

                        <option
                            value="yes"
                            @selected(request('onboarded') === 'yes')
                        >
                            Completed
                        </option>

                        <option
                            value="no"
                            @selected(request('onboarded') === 'no')
                        >
                            Not completed
                        </option>

                    </select>

                </div>

            </div>


            {{-- FILTER BUTTONS --}}

            <div class="mt-4 flex items-center gap-2">

                <button
                    type="submit"
                    class="rounded-lg bg-gray-900 px-5 py-2 text-sm font-medium text-white transition hover:bg-gray-800"
                >
                    Filter
                </button>


                @if(request()->anyFilled([
                    'search',
                    'language',
                    'gender',
                    'onboarded'
                ]))

                    <a
                        href="{{ route('admin.users.index') }}"
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50"
                    >
                        Reset
                    </a>

                @endif

            </div>

        </form>

    </div>


    {{-- =========================================================
        BULK FORM
    ========================================================== --}}

    <form
        id="bulk-form"
        method="POST"
        action="{{ route('admin.users.bulk-delete') }}"
    >

        @csrf


        {{-- =====================================================
            USERS TABLE
        ====================================================== --}}

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">


            <div
    id="bulk-toolbar"
    class="hidden border-b border-gray-100 bg-gray-50 px-5 py-3"
>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        <span class="text-xs font-semibold text-gray-700">

            <span id="selected-count">
                0
            </span>

            user(s) selected

        </span>


        <div class="flex flex-wrap items-center gap-2">

            {{-- ACTIONS DROPDOWN --}}
            <div class="relative">

                <button
                    type="button"
                    onclick="toggleBulkActions()"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                >
                    <i class="ik ik-settings text-sm"></i>
                    Actions
                    <i class="ik ik-chevron-down text-xs"></i>
                </button>


                <div
                    id="bulk-actions-menu"
                    class="absolute right-0 z-30 mt-2 hidden w-52 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg"
                >

                    <button
                        type="button"
                        onclick="bulkResetLives()"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <i class="ik ik-heart text-sm"></i>
                        Reset Lives
                    </button>


                    <button
                        type="button"
                        onclick="bulkResetStreak()"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <i class="ik ik-zap text-sm"></i>
                        Reset Streak
                    </button>


                    <div class="my-1 border-t border-gray-100"></div>


                    <button
                        type="button"
                        onclick="bulkActivate()"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <i class="ik ik-check-circle text-sm"></i>
                        Activate
                    </button>


                    <button
                        type="button"
                        onclick="bulkDisable()"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <i class="ik ik-slash text-sm"></i>
                        Disable
                    </button>


                    <div class="my-1 border-t border-gray-100"></div>


                    <button
                        type="button"
                        onclick="openBulkAmountModal('xp')"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <i class="ik ik-star text-sm"></i>
                        Adjust XP
                    </button>


                    <button
                        type="button"
                        onclick="openBulkAmountModal('coins')"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                    >
                        <i class="ik ik-dollar-sign text-sm"></i>
                        Adjust Coins
                    </button>

                </div>

            </div>


            {{-- DELETE --}}
            <button
                type="button"
                onclick="confirmBulkDelete()"
                class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-700"
            >
                <i class="ik ik-trash-2 text-sm"></i>
                Delete Selected
            </button>

        </div>

    </div>

</div>

        


            {{-- =================================================
                TABLE
            ================================================== --}}

            <div class="overflow-x-auto">

                <table class="w-full min-w-[1200px] text-left">


                    {{-- =================================================
                        HEADER
                    ================================================== --}}

                    <thead class="border-b border-gray-100 bg-gray-50/50">

                        <tr>


                            {{-- SELECT ALL --}}

                            <th class="w-12 px-4 py-3.5 text-center">

                                <input
                                    type="checkbox"
                                    id="select-all"
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500/20"
                                    aria-label="Select all users"
                                >

                            </th>


                            {{-- PLAYER --}}

                            <th class="w-[360px] min-w-[360px] px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500">

                                <a
                                    href="{{ request()->fullUrlWithQuery([
                                        'sort' => 'name',
                                        'direction' =>
                                            request('sort') === 'name'
                                            && request('direction') === 'asc'
                                                ? 'desc'
                                                : 'asc'
                                    ]) }}"
                                    class="inline-flex items-center gap-1.5 hover:text-gray-700"
                                >

                                    Player

                                    <i class="ik ik-arrow-up-down text-gray-400"></i>

                                </a>

                            </th>


                            {{-- XP --}}

                            <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                XP
                            </th>


                            {{-- SR --}}

                            <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                SR
                            </th>


                            {{-- STREAK --}}

                            <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Streak
                            </th>


                            {{-- COINS --}}

                            <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Coins
                            </th>


                            {{-- ANSWERS --}}

                            <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Answers
                            </th>


                            {{-- LAST PLAYED --}}

                            <th class="whitespace-nowrap px-5 py-3.5 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Last Played
                            </th>


                            {{-- ACTIONS --}}

                            <th class="w-28 whitespace-nowrap px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    {{-- =================================================
                        BODY
                    ================================================== --}}

                    <tbody class="divide-y divide-gray-100">

                        @forelse($users as $user)

                            <tr
                                class="group cursor-pointer transition-colors hover:bg-gray-50/80"
                                onclick="window.location='{{ route('admin.users.show', $user) }}'"
                            >


                                {{-- =================================================
                                    CHECKBOX
                                ================================================== --}}

                                <td
                                    class="px-4 py-4 text-center"
                                    onclick="event.stopPropagation()"
                                >

                                    <input
                                        type="checkbox"
                                        name="users[]"
                                        value="{{ $user->id }}"
                                        class="user-checkbox h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500/20"
                                        aria-label="Select {{ $user->name }}"
                                    >

                                </td>


                                {{-- =================================================
                                    PLAYER
                                ================================================== --}}

                                <td class="px-5 py-4">

                                    <a
                                        href="{{ route('admin.users.show', $user) }}"
                                        class="flex min-w-0 items-center gap-3"
                                        onclick="event.stopPropagation()"
                                    >


                                        {{-- AVATAR --}}

                                        <div class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-sm font-bold text-gray-500 ring-1 ring-gray-200">

                                            @if($user->avatar)

                                                <img
                                                    src="{{ asset('storage/' . ltrim($user->avatar, '/')) }}"
                                                    alt="{{ $user->name }}"
                                                    class="absolute inset-0 h-full w-full object-cover"
                                                    onerror="this.style.display='none'"
                                                >

                                            @endif

                                            <span>
                                                {{ strtoupper(
                                                    substr(
                                                        $user->name ?? 'U',
                                                        0,
                                                        1
                                                    )
                                                ) }}
                                            </span>

                                        </div>


                                        {{-- PLAYER INFORMATION --}}

                                        <div class="min-w-0 flex-1">


                                            {{-- FULL NAME --}}

                                            <p
                                                class="whitespace-normal break-words text-sm font-semibold leading-5 text-gray-800"
                                            >
                                                {{ $user->name ?? 'Unknown User' }}
                                            </p>


                                            {{-- USERNAME / TELEGRAM --}}

                                            <p class="mt-0.5 whitespace-normal break-all text-xs text-gray-500">

                                                @if($user->username)

                                                    {{ '@' . $user->username }}

                                                @elseif($user->telegram_id)

                                                    Telegram:
                                                    {{ $user->telegram_id }}

                                                @elseif($user->email)

                                                    {{ $user->email }}

                                                @else

                                                    No identifier

                                                @endif

                                            </p>

                                        </div>

                                    </a>

                                </td>


                                {{-- =================================================
                                    XP
                                ================================================== --}}

                                <td class="px-5 py-4 whitespace-nowrap">

                                    <p class="text-sm font-semibold text-gray-800">
                                        {{ number_format($user->total_xp) }}
                                    </p>

                                    <p class="text-xs text-gray-400">
                                        {{ number_format($user->weekly_xp) }}
                                        weekly
                                    </p>

                                </td>


                                {{-- =================================================
                                    SR
                                ================================================== --}}

                                <td class="px-5 py-4 whitespace-nowrap">

                                    <span class="inline-flex rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-600">

                                        {{ number_format(
                                            $user->current_sr,
                                            1
                                        ) }}

                                    </span>

                                </td>


                                {{-- =================================================
                                    STREAK
                                ================================================== --}}

                                <td class="px-5 py-4 whitespace-nowrap">

                                    <div class="flex items-center gap-1">

                                        <span class="text-orange-500">
                                            🔥
                                        </span>

                                        <span class="text-sm font-semibold text-gray-800">

                                            {{ number_format(
                                                $user->current_streak
                                            ) }}

                                        </span>

                                    </div>

                                    <p class="text-xs text-gray-400">

                                        Best:
                                        {{ number_format(
                                            $user->best_streak
                                        ) }}

                                    </p>

                                </td>


                                {{-- =================================================
                                    COINS
                                ================================================== --}}

                                <td class="px-5 py-4 whitespace-nowrap">

                                    <span class="text-sm font-medium text-gray-700">

                                        {{ number_format(
                                            $user->total_coins
                                        ) }}

                                    </span>

                                </td>


                                {{-- =================================================
                                    ANSWERS
                                ================================================== --}}

                                <td class="px-5 py-4 whitespace-nowrap">

                                    <span class="text-sm text-gray-700">

                                        {{ number_format(
                                            $user->total_answers_count
                                        ) }}

                                    </span>

                                </td>


                                {{-- =================================================
                                    LAST PLAYED
                                ================================================== --}}

                                <td class="px-5 py-4 whitespace-nowrap">

                                    @if($user->last_played_date)

                                        <p class="text-sm text-gray-700">

                                            {{ $user->last_played_date->format(
                                                'M d, Y'
                                            ) }}

                                        </p>

                                        @if($user->last_played_date->isToday())

                                            <span class="text-xs font-medium text-green-600">
                                                Active today
                                            </span>

                                        @endif

                                    @else

                                        <span class="text-xs text-gray-400">
                                            Never played
                                        </span>

                                    @endif

                                </td>


                                {{-- =================================================
                                    ACTIONS
                                ================================================== --}}

                                <td
                                    class="px-5 py-4 text-right whitespace-nowrap"
                                    onclick="event.stopPropagation()"
                                >

                                    <div class="inline-flex items-center justify-end gap-1">


                                        {{-- VIEW --}}

                                        <a
                                            href="{{ route('admin.users.show', $user) }}"
                                            class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                                            title="View Player"
                                        >

                                            <i class="ik ik-eye text-base"></i>

                                        </a>


                                        {{-- EDIT --}}

                                        <a
                                            href="{{ route('admin.users.edit', $user) }}"
                                            class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                            title="Edit Player"
                                        >

                                            <i class="ik ik-edit-2 text-base"></i>

                                        </a>


                                        {{-- DELETE --}}

                                        <button
                                            type="button"
                                            onclick="deleteSingleUser({{ $user->id }})"
                                            class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                                            title="Delete Player"
                                        >

                                            <i class="ik ik-trash-2 text-base"></i>

                                        </button>

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="9"
                                    class="px-5 py-12 text-center"
                                >

                                    <div class="mx-auto flex max-w-xs flex-col items-center justify-center text-center">

                                        <i class="ik ik-users text-4xl text-gray-300"></i>

                                        <p class="mt-2 text-sm font-medium text-gray-800">
                                            No users found
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            Try changing your search or filters.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- =================================================
                PAGINATION
            ================================================== --}}

            @if($users->hasPages())

                <div class="border-t border-gray-100 px-5 py-4">

                    {{ $users->withQueryString()->links() }}

                </div>

            @endif

        </div>

    </form>


    {{-- =========================================================
        SINGLE DELETE FORM
    ========================================================== --}}

    <form
        id="single-delete-form"
        method="POST"
        class="hidden"
    >

        @csrf

        @method('DELETE')

    </form>

    <div
    id="bulk-amount-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 px-4"
>

    <div class="w-full max-w-md rounded-xl bg-white shadow-xl">

        <div class="border-b border-gray-100 px-5 py-4">

            <h3
                id="bulk-amount-title"
                class="text-lg font-semibold text-gray-800"
            >
                Adjust XP
            </h3>

            <p class="mt-1 text-sm text-gray-500">

                Apply the adjustment to

                <span
                    id="bulk-amount-count"
                    class="font-semibold text-gray-700"
                >
                    0
                </span>

                selected user(s).

            </p>

        </div>


        <form
            id="bulk-amount-form"
            method="POST"
            class="p-5"
        >

            @csrf


            <div>

                <label
                    for="bulk-amount"
                    class="mb-1 block text-sm font-medium text-gray-700"
                >
                    Amount
                </label>

                <input
                    id="bulk-amount"
                    name="amount"
                    type="number"
                    value="0"
                    required
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                >

                <p class="mt-1 text-xs text-gray-400">
                    Use a negative number to remove XP or coins.
                </p>

            </div>


            <div id="bulk-amount-users"></div>


            <div class="mt-5 flex justify-end gap-2">

                <button
                    type="button"
                    onclick="closeBulkAmountModal()"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
                >
                    Cancel
                </button>


                <button
                    id="bulk-amount-submit"
                    type="submit"
                    class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800"
                >
                    Apply
                </button>

            </div>

        </form>

    </div>

</div>

    {{-- =========================================================
        CONFIRMATION MODAL
    ========================================================== --}}

    @include('admin.components.confirm-modal')

</div>


<script>

/*
|--------------------------------------------------------------------------
| CONFIRM STORE
|--------------------------------------------------------------------------
*/

function registerConfirmStore() {

    if (!window.Alpine) {
        return;
    }

    if (
        !Alpine.store('confirm') ||
        typeof Alpine.store('confirm').ask !== 'function'
    ) {

        Alpine.store('confirm', {

            show: false,

            title: '',

            message: '',

            icon: 'ik ik-trash-2',

            tone: 'danger',

            cancelText: 'Cancel',

            confirmText: 'Delete',

            onConfirm: null,


            ask(options) {

                this.title =
                    options.title ||
                    'Are you sure?';

                this.message =
                    options.message ||
                    'This action cannot be undone.';

                this.icon =
                    options.icon ||
                    'ik ik-trash-2';

                this.tone =
                    options.tone ||
                    'danger';

                this.cancelText =
                    options.cancelText ||
                    'Cancel';

                this.confirmText =
                    options.confirmText ||
                    'Delete';

                this.onConfirm =
                    options.onConfirm ||
                    null;

                this.show = true;

            },


            confirm() {

                if (
                    typeof this.onConfirm ===
                    'function'
                ) {

                    this.onConfirm();

                }

                this.show = false;

            },


            cancel() {

                this.show = false;

            }

        });

    }

}


/*
|--------------------------------------------------------------------------
| REGISTER ALPINE STORE
|--------------------------------------------------------------------------
*/

if (window.Alpine) {

    registerConfirmStore();

} else {

    document.addEventListener(
        'alpine:init',
        registerConfirmStore
    );

}


/*
|--------------------------------------------------------------------------
| CHECKBOX / BULK TOOLBAR
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const selectAllCheckbox =
            document.getElementById(
                'select-all'
            );

        const userCheckboxes =
            document.querySelectorAll(
                '.user-checkbox'
            );

        const bulkToolbar =
            document.getElementById(
                'bulk-toolbar'
            );

        const selectedCountSpan =
            document.getElementById(
                'selected-count'
            );


        /*
        |--------------------------------------------------------------------------
        | Update toolbar
        |--------------------------------------------------------------------------
        */

        function updateToolbar() {

            const checkedCount =
                document.querySelectorAll(
                    '.user-checkbox:checked'
                ).length;


            /*
            |--------------------------------------------------------------------------
            | Selected count
            |--------------------------------------------------------------------------
            */

            if (selectedCountSpan) {

                selectedCountSpan.textContent =
                    checkedCount;

            }


            /*
            |--------------------------------------------------------------------------
            | Show / hide toolbar
            |--------------------------------------------------------------------------
            */

            if (bulkToolbar) {

                if (checkedCount > 0) {

                    bulkToolbar.classList.remove(
                        'hidden'
                    );

                } else {

                    bulkToolbar.classList.add(
                        'hidden'
                    );

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Select all state
            |--------------------------------------------------------------------------
            */

            if (selectAllCheckbox) {

                selectAllCheckbox.checked =
                    checkedCount > 0 &&
                    checkedCount ===
                    userCheckboxes.length;

                selectAllCheckbox.indeterminate =
                    checkedCount > 0 &&
                    checkedCount <
                    userCheckboxes.length;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Select all
        |--------------------------------------------------------------------------
        */

        if (selectAllCheckbox) {

            selectAllCheckbox.addEventListener(
                'change',
                function () {

                    userCheckboxes.forEach(
                        checkbox => {

                            checkbox.checked =
                                selectAllCheckbox.checked;

                        }
                    );

                    updateToolbar();

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Individual checkboxes
        |--------------------------------------------------------------------------
        */

        userCheckboxes.forEach(
            checkbox => {

                checkbox.addEventListener(
                    'change',
                    updateToolbar
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Initial state
        |--------------------------------------------------------------------------
        */

        updateToolbar();

    }
);


/*
|--------------------------------------------------------------------------
| CLEAR SELECTION
|--------------------------------------------------------------------------
*/

function clearUserSelection() {

    const checkboxes =
        document.querySelectorAll(
            '.user-checkbox'
        );

    checkboxes.forEach(
        checkbox => {

            checkbox.checked = false;

        }
    );


    const selectAll =
        document.getElementById(
            'select-all'
        );

    if (selectAll) {

        selectAll.checked = false;

        selectAll.indeterminate = false;

    }


    const toolbar =
        document.getElementById(
            'bulk-toolbar'
        );

    if (toolbar) {

        toolbar.classList.add(
            'hidden'
        );

    }


    const count =
        document.getElementById(
            'selected-count'
        );

    if (count) {

        count.textContent = '0';

    }

}


/*
|--------------------------------------------------------------------------
| BULK DELETE
|--------------------------------------------------------------------------
*/

function confirmBulkDelete() {

    const count =
        document.querySelectorAll(
            '.user-checkbox:checked'
        ).length;

    if (count === 0) {
        return;
    }

    if (
        window.Alpine &&
        Alpine.store('confirm')
    ) {

        Alpine.store('confirm').ask({

            title:
                'Delete Selected Users?',

            message:
                `Are you sure you want to permanently delete ${count} user(s)? This action cannot be undone.`,

            icon:
                'ik ik-trash-2',

            tone:
                'danger',

            confirmText:
                'Yes, Delete All',

            cancelText:
                'Cancel',

            onConfirm: () => {

                document
                    .getElementById('bulk-form')
                    .submit();

            }

        });

    }
}


function getSelectedUserIds() {

    return Array.from(
        document.querySelectorAll(
            '.user-checkbox:checked'
        )
    ).map(
        checkbox => checkbox.value
    );

}


function getSelectedUserCount() {

    return getSelectedUserIds().length;

}


/*
|--------------------------------------------------------------------------
| BULK ACTION MENU
|--------------------------------------------------------------------------
*/

function toggleBulkActions() {

    const menu =
        document.getElementById(
            'bulk-actions-menu'
        );

    if (!menu) {
        return;
    }

    menu.classList.toggle('hidden');

}


document.addEventListener(
    'click',
    function (event) {

        const menu =
            document.getElementById(
                'bulk-actions-menu'
            );

        const button =
            event.target.closest(
                '[onclick="toggleBulkActions()"]'
            );

        if (
            menu &&
            !menu.contains(event.target) &&
            !button
        ) {
            menu.classList.add('hidden');
        }

    }
);


/*
|--------------------------------------------------------------------------
| SUBMIT BULK ACTION
|--------------------------------------------------------------------------
*/

function submitBulkAction(
    action,
    title,
    message,
    confirmText = 'Confirm'
) {

    const ids =
        getSelectedUserIds();

    if (!ids.length) {
        return;
    }

    if (
        !window.Alpine ||
        !Alpine.store('confirm')
    ) {
        return;
    }

    Alpine.store('confirm').ask({

        title: title,

        message: message,

        icon: 'ik ik-check-circle',

        tone: 'warning',

        confirmText: confirmText,

        cancelText: 'Cancel',

        onConfirm: () => {

            const form =
                document.createElement('form');

            form.method = 'POST';

            form.action = action;

            form.style.display = 'none';


            const csrf =
                document.createElement('input');

            csrf.type = 'hidden';

            csrf.name = '_token';

            csrf.value =
                '{{ csrf_token() }}';

            form.appendChild(csrf);


            ids.forEach(id => {

                const input =
                    document.createElement('input');

                input.type = 'hidden';

                input.name = 'users[]';

                input.value = id;

                form.appendChild(input);

            });


            document.body.appendChild(form);

            form.submit();

        }

    });

}


/*
|--------------------------------------------------------------------------
| RESET LIVES
|--------------------------------------------------------------------------
*/

function bulkResetLives() {

    const count =
        getSelectedUserCount();

    if (!count) {
        return;
    }

    submitBulkAction(

        '{{ route('admin.users.bulk-reset-lives') }}',

        'Reset Lives?',

        `Reset daily lives to 5 for ${count} selected user(s)?`,

        'Reset Lives'

    );

}


/*
|--------------------------------------------------------------------------
| RESET STREAK
|--------------------------------------------------------------------------
*/

function bulkResetStreak() {

    const count =
        getSelectedUserCount();

    if (!count) {
        return;
    }

    submitBulkAction(

        '{{ route('admin.users.bulk-reset-streak') }}',

        'Reset Streak?',

        `Reset the current streak for ${count} selected user(s)?`,

        'Reset Streak'

    );

}


/*
|--------------------------------------------------------------------------
| ACTIVATE
|--------------------------------------------------------------------------
*/

function bulkActivate() {

    const count =
        getSelectedUserCount();

    if (!count) {
        return;
    }

    submitBulkAction(

        '{{ route('admin.users.bulk-activate') }}',

        'Activate Users?',

        `Activate ${count} selected user(s)?`,

        'Activate'

    );

}


/*
|--------------------------------------------------------------------------
| DISABLE
|--------------------------------------------------------------------------
*/

function bulkDisable() {

    const count =
        getSelectedUserCount();

    if (!count) {
        return;
    }

    submitBulkAction(

        '{{ route('admin.users.bulk-disable') }}',

        'Disable Users?',

        `Disable ${count} selected user(s)? They will no longer be active.`,

        'Disable'

    );

}


/*
|--------------------------------------------------------------------------
| AMOUNT MODAL
|--------------------------------------------------------------------------
*/

let bulkAmountType = null;


function openBulkAmountModal(type) {

    const ids =
        getSelectedUserIds();

    if (!ids.length) {
        return;
    }

    bulkAmountType =
        type;


    const modal =
        document.getElementById(
            'bulk-amount-modal'
        );

    const title =
        document.getElementById(
            'bulk-amount-title'
        );

    const count =
        document.getElementById(
            'bulk-amount-count'
        );

    const form =
        document.getElementById(
            'bulk-amount-form'
        );

    const amount =
        document.getElementById(
            'bulk-amount'
        );


    if (type === 'xp') {

        title.textContent =
            'Adjust XP';

        form.action =
            '{{ route('admin.users.bulk-adjust-xp') }}';

    } else {

        title.textContent =
            'Adjust Coins';

        form.action =
            '{{ route('admin.users.bulk-adjust-coins') }}';

    }


    count.textContent =
        ids.length;

    amount.value =
        0;


    /*
     * Add selected IDs.
     */

    const container =
        document.getElementById(
            'bulk-amount-users'
        );

    container.innerHTML = '';


    ids.forEach(id => {

        const input =
            document.createElement('input');

        input.type = 'hidden';

        input.name = 'users[]';

        input.value = id;

        container.appendChild(input);

    });


    modal.classList.remove(
        'hidden'
    );

    modal.classList.add(
        'flex'
    );


    setTimeout(
        () => amount.focus(),
        50
    );

}


function closeBulkAmountModal() {

    const modal =
        document.getElementById(
            'bulk-amount-modal'
        );

    if (!modal) {
        return;
    }

    modal.classList.add(
        'hidden'
    );

    modal.classList.remove(
        'flex'
    );

    bulkAmountType =
        null;

}


/*
|--------------------------------------------------------------------------
| ESCAPE MODAL
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function (event) {

        if (
            event.key === 'Escape'
        ) {

            closeBulkAmountModal();

        }

    }
);

/*
|--------------------------------------------------------------------------
| SINGLE DELETE
|--------------------------------------------------------------------------
*/

function deleteSingleUser(id) {

    if (
        window.Alpine &&
        Alpine.store('confirm')
    ) {

        Alpine.store('confirm').ask({

            title:
                'Delete User?',

            message:
                'Are you sure you want to permanently delete this user? This action cannot be undone.',

            icon:
                'ik ik-trash-2',

            tone:
                'danger',

            confirmText:
                'Delete',

            cancelText:
                'Cancel',

            onConfirm: () => {

                const form =
                    document.getElementById(
                        'single-delete-form'
                    );


                form.action =
                    `/admin/users/${id}`;


                form.submit();

            }

        });

    }

}

</script>

@endsection