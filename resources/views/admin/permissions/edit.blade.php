@extends('admin.layouts.main')

@section('title', 'Edit Permission')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <a
        href="{{ route('admin.permissions.index') }}"
        class="inline-flex items-center gap-1 text-xs font-semibold text-gray-500 hover:text-indigo-600 transition"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
        </svg>
        Back to Permissions
    </a>

    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200/60">
        <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-4">
            <h1 class="text-base font-bold text-gray-900">Edit Permission</h1>
            <p class="mt-0.5 text-xs text-gray-500">Updating permission identifier: <code class="font-mono text-indigo-600 font-semibold">{{ $permission->name }}</code></p>
        </div>

        <form method="POST" action="{{ route('admin.permissions.update', $permission) }}" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700">
                    Permission Name <span class="text-rose-500">*</span>
                </label>
                <div class="mt-2">
                    <input
                        type="text"
                        name="name"
                        id="name"
                        value="{{ old('name', $permission->name) }}"
                        required
                        class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-mono"
                    >
                </div>
                @error('name')
                    <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <a
                    href="{{ route('admin.permissions.index') }}"
                    class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-500 cursor-pointer"
                >
                    Update Permission
                </button>
            </div>
        </form>
    </div>

</div>
@endsection