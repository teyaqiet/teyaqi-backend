<form action="{{ $action }}" method="POST" class="space-y-8" id="roleForm">
    @csrf
    @if(isset($method) && strtoupper($method) !== 'POST')
        @method($method)
    @endif

    {{-- Custom Switch Styles --}}
    <style>
        .toggle-switch:checked + .toggle-bg {
            background-color: #4f46e5;
        }
        .toggle-switch:checked + .toggle-bg .toggle-dot {
            transform: translateX(1.25rem);
        }
    </style>

    {{-- Role Identity Card --}}
    <div class="overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200/60 md:p-8 space-y-6">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-500/10">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </div>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Role Identity</h2>
                <p class="text-xs text-gray-500">Provide a distinct name for this system role.</p>
            </div>
        </div>

        <div class="max-w-xl">
            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-gray-700">
                Role Name <span class="text-rose-500">*</span>
            </label>
            <div class="mt-2 relative">
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name', $role->name ?? '') }}"
                    placeholder="e.g. Editor, Moderator"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 @error('name') border-rose-500 bg-rose-50/10 @enderror"
                    required
                >
            </div>
            @error('name')
                <p class="mt-1.5 text-xs font-medium text-rose-500">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Permissions Matrix Card --}}
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200/60">
        
        {{-- Matrix Header & Controls --}}
        <div class="p-6 md:p-8 border-b border-gray-100 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-amber-500/10">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-semibold text-gray-900">Permissions Matrix</h2>
                        <span id="selectedCountBadge" class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                            0 selected
                        </span>
                    </div>
                    <p class="text-xs text-gray-500">Toggle capabilities per resource using custom switches.</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    onclick="selectAllPermissions(true)"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 hover:text-indigo-600 transition cursor-pointer"
                >
                    Select All
                </button>
                <button
                    type="button"
                    onclick="selectAllPermissions(false)"
                    class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 hover:text-rose-600 transition cursor-pointer"
                >
                    Deselect All
                </button>
            </div>
        </div>

        {{-- Resource Cards / Custom Layout Matrix --}}
        <div class="divide-y divide-gray-100">
            @php
                // Ordered list of action keys mapped to human-readable labels
                $orderedActions = [
                    'view'            => 'View (own)',
                    'view_any'        => 'ViewAny',
                    'create'          => 'Create',
                    'update'          => 'Update',
                    'delete'          => 'Delete (own)',
                    'delete_any'      => 'DeleteAny',
                    'restore'         => 'Restore',
                    'force_delete'    => 'ForceDelete (own)',
                    'force_delete_any'=> 'ForceDeleteAny',
                    'restore_any'     => 'RestoreAny',
                    'replicate'       => 'Replicate',
                    'reorder'         => 'Reorder',
                ];
            @endphp

            @foreach($permissions as $resource => $actions)
                @php $resourceSlug = Str::slug($resource); @endphp
                <div class="p-6 md:p-8 space-y-4 hover:bg-gray-50/40 transition group-row-{{ $resourceSlug }}">
                    
                    {{-- Module Header & Row Controls --}}
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <div class="flex items-center gap-2.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-indigo-500"></span>
                            <h3 class="text-sm font-bold text-gray-900 capitalize tracking-wide">
                                {{ str_replace(['_', '-'], ' ', $resource) }}
                            </h3>
                        </div>

                        <button
                            type="button"
                            onclick="toggleRow('{{ $resourceSlug }}')"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition cursor-pointer bg-indigo-50/50 hover:bg-indigo-50 px-2.5 py-1 rounded-md"
                        >
                            Toggle Row
                        </button>
                    </div>

                    {{-- Actions Switches Grid --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach($orderedActions as $actionKey => $displayLabel)
                            @if(isset($actions[$actionKey]))
                                @php
                                    $permission = $actions[$actionKey];
                                    $hasPerm = isset($role) && $role->permissions->contains('id', $permission->id);
                                    $isChecked = old('permissions') !== null 
                                        ? in_array($permission->name, old('permissions', [])) 
                                        : $hasPerm;
                                @endphp

                                <label class="flex items-center justify-between p-3 rounded-xl border border-gray-200/80 bg-white hover:border-indigo-200 hover:shadow-sm transition cursor-pointer select-none">
                                    <span class="text-xs font-medium text-gray-700">
                                        {{ $displayLabel }}
                                    </span>
                                    
                                    {{-- Switch Component --}}
                                    <div class="relative inline-block w-9 align-middle select-none">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission->name }}"
                                            @checked($isChecked)
                                            class="toggle-switch sr-only permission-checkbox"
                                            onchange="updateCounter()"
                                        >
                                        <div class="toggle-bg block h-5 w-9 rounded-full bg-gray-200 transition-colors duration-200 ease-in-out">
                                            <div class="toggle-dot absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200 ease-in-out"></div>
                                        </div>
                                    </div>
                                </label>
                            @else
                                {{-- Disabled Placeholder when action is not registered --}}
                                <div class="flex items-center justify-between p-3 rounded-xl border border-dashed border-gray-100 bg-gray-50/50 opacity-40 select-none">
                                    <span class="text-xs text-gray-400">
                                        {{ $displayLabel }}
                                    </span>
                                    <span class="text-gray-300 text-xs font-mono">—</span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    {{-- Unmapped / Custom Actions Fallback --}}
                    @php
                        $customActions = array_diff_key($actions, $orderedActions);
                    @endphp
                    @if(count($customActions) > 0)
                        <div class="pt-2 border-t border-gray-100/60">
                            <span class="text-[10px] uppercase font-bold text-gray-400 tracking-wider mb-2 block">Custom Actions</span>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                @foreach($customActions as $customKey => $permission)
                                    @php
                                        $hasPerm = isset($role) && $role->permissions->contains('id', $permission->id);
                                        $isChecked = old('permissions') !== null 
                                            ? in_array($permission->name, old('permissions', [])) 
                                            : $hasPerm;
                                    @endphp
                                    <label class="flex items-center justify-between p-3 rounded-xl border border-gray-200/80 bg-white hover:border-indigo-200 hover:shadow-sm transition cursor-pointer select-none">
                                        <span class="text-xs font-medium text-gray-700 capitalize">
                                            {{ str_replace(['_', '-'], ' ', $customKey) }}
                                        </span>
                                        <div class="relative inline-block w-9 align-middle select-none">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->name }}"
                                                @checked($isChecked)
                                                class="toggle-switch sr-only permission-checkbox"
                                                onchange="updateCounter()"
                                            >
                                            <div class="toggle-bg block h-5 w-9 rounded-full bg-gray-200 transition-colors duration-200 ease-in-out">
                                                <div class="toggle-dot absolute top-0.5 left-0.5 h-4 w-4 rounded-full bg-white shadow transition-transform duration-200 ease-in-out"></div>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    </div>

    {{-- Form Submit Controls --}}
    <div class="flex items-center justify-between pt-2">
        <a
            href="{{ route('admin.roles.index') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
        >
            Cancel
        </a>

        <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 cursor-pointer"
        >
            {{ $button ?? 'Save Role' }}
        </button>
    </div>
</form>

{{-- Interactivity Script --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        updateCounter();
    });

    function updateCounter() {
        const checked = document.querySelectorAll('.permission-checkbox:checked').length;
        const total = document.querySelectorAll('.permission-checkbox').length;
        const badge = document.getElementById('selectedCountBadge');
        if (badge) {
            badge.innerText = `${checked} of ${total} selected`;
        }
    }

    function toggleRow(resourceSlug) {
        const checkboxes = document.querySelectorAll(`.group-row-${resourceSlug} .permission-checkbox`);
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        updateCounter();
    }

    function selectAllPermissions(status) {
        const checkboxes = document.querySelectorAll('.permission-checkbox');
        checkboxes.forEach(cb => cb.checked = status);
        updateCounter();
    }
</script>