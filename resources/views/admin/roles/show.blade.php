@extends('admin.layouts.main')

@section('title', 'Role Details - ' . ucfirst($role->name))

@section('content')
<div class="space-y-8">

    {{-- Top Navigation & Header --}}
    <div>
        <a
            href="{{ route('admin.roles.index') }}"
            class="inline-flex items-center gap-1 text-xs font-semibold text-gray-500 hover:text-indigo-600 transition mb-4"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to Roles
        </a>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-xl font-bold text-indigo-600 ring-1 ring-indigo-500/10">
                    {{ strtoupper(substr($role->name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ ucfirst($role->name) }}</h1>
                    <p class="mt-0.5 text-xs text-gray-500 font-mono">Role ID: #{{ $role->id }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a
                    href="{{ route('admin.roles.edit', $role) }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 hover:text-indigo-600"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    Edit Role
                </a>

                <form
                    method="POST"
                    action="{{ route('admin.roles.destroy', $role) }}"
                    onsubmit="return confirm('Are you sure you want to delete this role? Users with this role will lose their assigned access levels.');"
                    class="inline-block"
                >
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-100 hover:border-rose-300 cursor-pointer"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                        Delete Role
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Role Overview Cards --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        
        {{-- Total Users Card --}}
        <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Assigned Users</p>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-gray-900">{{ number_format($role->users_count ?? $role->users->count()) }}</span>
                <span class="text-xs text-gray-500">active profiles</span>
            </div>
        </div>

        {{-- Total Permissions Card --}}
        <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Granted Permissions</p>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold tracking-tight text-gray-900">{{ number_format($role->permissions->count()) }}</span>
                <span class="text-xs text-gray-500">privileges granted</span>
            </div>
        </div>

        {{-- Metadata Card --}}
        <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Created Date</p>
            <div class="mt-2">
                <span class="text-base font-bold text-gray-900">{{ $role->created_at->format('F d, Y') }}</span>
                <p class="text-xs text-gray-400 mt-0.5">{{ $role->created_at->diffForHumans() }}</p>
            </div>
        </div>

    </div>

    {{-- Permissions Grid Card --}}
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Assigned Permissions</h2>
        
        @if ($role->permissions->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                @foreach ($role->permissions as $permission)
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-50/80 px-3 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                        <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        {{ $permission->name }}
                    </span>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center">
                <p class="text-sm font-semibold text-gray-700">No permissions attached</p>
                <p class="mt-1 text-xs text-gray-400">Edit this role to grant explicit system permissions.</p>
            </div>
        @endif
    </div>

    {{-- Users Assigned Section --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200/60">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="text-base font-semibold text-gray-900">Users with {{ ucfirst($role->name) }} Role</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="border-b border-gray-100 bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">User</th>
                        <th class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Joined</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($role->users as $user)
                        <tr class="transition hover:bg-gray-50/60">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-50 text-xs font-bold text-indigo-600 ring-1 ring-indigo-500/10">
                                        @if (isset($user->avatar) && $user->avatar)
                                            <img
                                                src="{{ rtrim(config('app.frontend_url'), '/') . '/avatar/' . ltrim($user->avatar, '/') }}"
                                                alt="{{ $user->name }}"
                                                class="h-9 w-9 rounded-full object-cover"
                                            >
                                        @else
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $user->email ?? $user->username }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $user->created_at ? $user->created_at->format('M d, Y') : 'N/A' }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium">
                                <a
                                    href="{{ route('admin.users.show', $user) }}"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition"
                                >
                                    View Profile
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-xs text-gray-400">
                                No users currently assigned to this role.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection