@extends('admin.layouts.main')

@section('title', 'Admin Users — Teyaqi')

@section('content')
<div class="w-full space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-gray-900">Admin Users</h1>
            <p class="mt-1 text-xs text-gray-500">Manage administrative accounts, access credentials, and system roles.</p>
        </div>

        <a href="{{ route('admin.admin-users.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-indigo-700 shadow-lg shadow-indigo-600/20">
            <i class="ik ik-user-plus text-sm"></i>
            <span>Add Admin User</span>
        </a>
    </div>

    {{-- Status Flash Alert --}}
    @if (session('status'))
        <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-xs font-medium text-emerald-700 shadow-sm">
            <div class="flex items-center gap-2">
                <i class="ik ik-check-circle text-base"></i>
                <span>{{ session('status') }}</span>
            </div>
        </div>
    @endif

    {{-- Data Table Container --}}
    <div class="w-full rounded-2xl border border-gray-100 bg-white shadow-xl shadow-black/5">
        
        {{-- Table Toolbar / Filters --}}
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div class="relative w-full sm:w-72">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <i class="ik ik-search text-sm"></i>
                </span>
                <input type="text" 
                       id="table-search" 
                       placeholder="Search users..." 
                       class="w-full rounded-xl border border-gray-200 py-2 pl-9 pr-3 text-xs text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div class="flex items-center gap-2 text-xs text-gray-500">
                <span>Total: <strong class="text-gray-800 font-semibold">{{ $users->total() ?? count($users) }}</strong> users</span>
            </div>
        </div>

        {{-- Table View --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-50/50 uppercase tracking-wider text-[11px] font-semibold text-gray-500 border-b border-gray-100">
                    <tr>
                        <th scope="col" class="py-3.5 px-6">User</th>
                        <th scope="col" class="py-3.5 px-4">Role</th>
                        <th scope="col" class="py-3.5 px-4">Created At</th>
                        <th scope="col" class="py-3.5 px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50/50 transition">
                            
                            {{-- User Name & Email --}}
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 font-bold text-xs ring-1 ring-indigo-100">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 text-sm">{{ $user->name }}</div>
                                        <div class="text-gray-400 text-xs">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Role Badge --}}
                            <td class="py-4 px-4 whitespace-nowrap">
                                @forelse($user->roles as $role)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                                        <i class="ik ik-shield text-[10px]"></i>
                                        {{ ucfirst($role->name) }}
                                    </span>
                                @empty
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-500">
                                        No Role
                                    </span>
                                @endforelse
                            </td>

                            {{-- Created Date --}}
                            <td class="py-4 px-4 whitespace-nowrap text-gray-500">
                                {{ $user->created_at ? $user->created_at->format('M d, Y') : '—' }}
                            </td>

                            {{-- Action Buttons --}}
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.admin-users.edit', $user) }}" 
                                       class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-indigo-600 transition"
                                       title="Edit User">
                                        <i class="ik ik-edit-2 text-xs"></i>
                                    </a>

                                    @if(auth()->id() !== $user->id)
                                        <form method="POST" action="{{ route('admin.admin-users.destroy', $user) }}" onsubmit="return confirm('Are you sure you want to delete {{ $user->name }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-rose-100 text-rose-500 hover:bg-rose-50 hover:text-rose-600 transition"
                                                    title="Delete User">
                                                <i class="ik ik-trash-2 text-xs"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <i class="ik ik-users text-3xl text-gray-300"></i>
                                    <p class="text-sm font-medium">No admin users found.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        @if(method_exists($users, 'hasPages') && $users->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $users->links() }}
            </div>
        @endif

    </div>

</div>
@endsection