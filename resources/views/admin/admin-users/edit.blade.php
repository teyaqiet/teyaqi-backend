@extends('admin.layouts.main')

@section('title', 'Edit Admin User — Teyaqi')

@section('content')
<div class="w-full space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-gray-900">Edit Admin User</h1>
            <p class="mt-1 text-xs text-gray-500">Update account details or assigned access role for {{ $user->name }}.</p>
        </div>

        <a href="{{ route('admin.admin-users.index') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50 transition">
            <i class="ik ik-arrow-left text-sm"></i>
            <span>Back to List</span>
        </a>
    </div>

    {{-- Form Card --}}
    <div class="w-full rounded-2xl border border-gray-100 bg-white p-6 shadow-xl shadow-black/5 sm:p-8">
        <form method="POST" action="{{ route('admin.admin-users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Full Name --}}
                <div>
                    <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-gray-600">Full Name</label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="ik ik-user"></i>
                        </span>
                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name', $user->name) }}"
                               required
                               class="w-full rounded-xl border @error('name') border-rose-300 bg-rose-50/30 @else border-gray-200 @enderror py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                               placeholder="John Doe">
                    </div>
                    @error('name')
                        <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email Address --}}
                <div>
                    <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-gray-600">Email Address</label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="ik ik-mail"></i>
                        </span>
                        <input type="email"
                               name="email"
                               id="email"
                               value="{{ old('email', $user->email) }}"
                               required
                               class="w-full rounded-xl border @error('email') border-rose-300 bg-rose-50/30 @else border-gray-200 @enderror py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                               placeholder="admin@teyaqi.com">
                    </div>
                    @error('email')
                        <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password (Optional) --}}
                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                        New Password <span class="normal-case text-gray-400 font-normal">(Leave blank to keep current)</span>
                    </label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="ik ik-lock"></i>
                        </span>
                        <input type="password"
                               name="password"
                               id="password"
                               class="w-full rounded-xl border @error('password') border-rose-300 bg-rose-50/30 @else border-gray-200 @enderror py-2.5 pl-9 pr-3 text-sm text-gray-800 placeholder-gray-400 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
                               placeholder="••••••••">
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Assigned Role --}}
                <div>
                    <label for="role" class="block text-xs font-semibold uppercase tracking-wider text-gray-600">Assigned Role</label>
                    <div class="relative mt-1.5">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="ik ik-shield"></i>
                        </span>
                        <select name="role"
                                id="role"
                                required
                                class="w-full rounded-xl border @error('role') border-rose-300 bg-rose-50/30 @else border-gray-200 @enderror py-2.5 pl-9 pr-8 text-sm text-gray-800 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 appearance-none bg-white">
                            <option value="" disabled>Select a role...</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ old('role', $user->roles->first()?->name) === $role->name ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                            <i class="ik ik-chevron-down"></i>
                        </span>
                    </div>
                    @error('role')
                        <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-100">
                <a href="{{ route('admin.admin-users.index') }}"
                   class="rounded-xl px-4 py-2.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="flex items-center justify-center gap-2 rounded-xl bg-indigo-600 py-2.5 px-5 text-xs font-semibold text-white transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-indigo-700 shadow-lg shadow-indigo-600/20">
                    <i class="ik ik-check text-sm"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection