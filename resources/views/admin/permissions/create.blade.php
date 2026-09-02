@extends('admin.layouts.main')

@section('title', 'Create Permission')

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
            <h1 class="text-base font-bold text-gray-900">Create New Permission</h1>
            <p class="mt-0.5 text-xs text-gray-500">Select the access action and target resource module to generate system permissions.</p>
        </div>

        <form method="POST" action="{{ route('admin.permissions.store') }}" class="p-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {{-- Action Select --}}
                <div>
                    <label for="action" class="block text-xs font-semibold uppercase tracking-wider text-gray-700">
                        Action Function <span class="text-rose-500">*</span>
                    </label>
                    <div class="mt-2">
                        <select
                            name="action"
                            id="action"
                            required
                            class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm bg-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-medium"
                        >
                            <option value="" disabled {{ old('action') ? '' : 'selected' }}>Select an action...</option>
                            @foreach($actions as $key => $label)
                                <option value="{{ $key }}" {{ old('action') === $key ? 'selected' : '' }}>
                                    {{ $label }} ({{ $key }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @error('action')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Resource Input --}}
                <div>
                    <label for="resource" class="block text-xs font-semibold uppercase tracking-wider text-gray-700">
                        Resource Module <span class="text-rose-500">*</span>
                    </label>
                    <div class="mt-2">
                        <input
                            type="text"
                            name="resource"
                            id="resource"
                            value="{{ old('resource') }}"
                            placeholder="e.g. users, posts, reports"
                            required
                            class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 font-mono"
                        >
                    </div>
                    @error('resource')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Dynamic Live Preview Card --}}
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-900">Generated Permission Key</p>
                    <p class="text-xs text-indigo-600 mt-0.5">This key will be evaluated by your authorization gates.</p>
                </div>
                <code id="permission-preview" class="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-mono font-bold text-white shadow-sm">
                    action:resource
                </code>
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
                    Save Permission
                </button>
            </div>
        </form>
    </div>

</div>

{{-- Interactive Live Preview Script --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const actionSelect = document.getElementById('action');
        const resourceInput = document.getElementById('resource');
        const preview = document.getElementById('permission-preview');

        function updatePreview() {
            const action = actionSelect.value || 'action';
            const resource = resourceInput.value.trim().toLowerCase().replace(/[^a-z0-9_-]/g, '') || 'resource';
            preview.textContent = `${action}:${resource}`;
        }

        actionSelect.addEventListener('change', updatePreview);
        resourceInput.addEventListener('input', updatePreview);
        updatePreview();
    });
</script>
@endsection