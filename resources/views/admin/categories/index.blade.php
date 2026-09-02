@extends('admin.layouts.main')

@section('title', 'Categories')

@section('content')
<div class="space-y-6">

    {{-- FLASH MESSAGES --}}
    @if(session('success'))
        <div class="flex items-center justify-between rounded-xl bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
            <div class="flex items-center gap-2">
                <i class="ik ik-check-circle text-lg text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center justify-between rounded-xl bg-rose-50 p-4 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-600/20">
            <div class="flex items-center gap-2">
                <i class="ik ik-alert-circle text-lg text-rose-600"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900">&times;</button>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Categories
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Manage Teyaqi question categories.
            </p>
        </div>

        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-gray-500">
                {{ number_format($categories->total()) }} {{ Str::plural('Category', $categories->total()) }}
            </span>

            <a
                href="{{ route('admin.categories.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
                <i class="ik ik-plus text-base"></i> Add Category
            </a>
        </div>
    </div>

    {{-- BULK ACTIONS FORM WRAPPER --}}
    <form id="bulk-form" method="POST" action="{{ route('admin.categories.bulk-delete') }}">
        @csrf

        {{-- CATEGORIES TABLE CONTAINER --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
            
            {{-- BULK ACTIONS TOOLBAR --}}
            <div id="bulk-toolbar" class="hidden border-b border-gray-100 bg-rose-50/50 px-5 py-3 transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-rose-900">
                        <span id="selected-count">0</span> category(ies) selected
                    </span>
                    <button
                        type="button"
                        onclick="confirmBulkDelete()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-rose-700"
                    >
                        <i class="ik ik-trash-2 text-sm"></i> Delete Selected
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-fixed text-left">
                    <thead class="border-b border-gray-100 bg-gray-50/50">
                        <tr>
                            <th class="w-[4%] px-4 py-3.5 text-center">
                                <input 
                                    type="checkbox" 
                                    id="select-all" 
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500/20"
                                >
                            </th>

                            <th class="w-[40%] px-4 py-3.5 text-xs font-semibold uppercase text-gray-500">Category</th>
                            <th class="w-[15%] px-4 py-3.5 text-xs font-semibold uppercase text-gray-500">Questions</th>
                            <th class="w-[12%] px-4 py-3.5 text-xs font-semibold uppercase text-gray-500">Order</th>
                            <th class="w-[15%] px-4 py-3.5 text-xs font-semibold uppercase text-gray-500">Status</th>
                            <th class="w-[14%] px-4 py-3.5 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse($categories as $category)
                            <tr class="group transition-colors hover:bg-gray-50/80">
                                {{-- CHECKBOX COLUMN --}}
                                <td class="px-4 py-4 text-center align-middle" onclick="event.stopPropagation()">
                                    <input 
                                        type="checkbox" 
                                        name="categories[]" 
                                        value="{{ $category->id }}"
                                        class="category-checkbox h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500/20"
                                    >
                                </td>

                                {{-- CATEGORY INFO --}}
                                <td class="px-4 py-4 align-middle">
                                    <div class="flex items-center gap-3.5">
                                        {{-- ICON --}}
                                        @if($category->icon)
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-white shadow-xs"
                                                style="background: {{ $category->color ?? '#6b7280' }}"
                                            >
                                                <i class="{{ $category->icon }} text-lg"></i>
                                            </div>
                                        @else
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400">
                                                <i class="ik ik-grid text-lg"></i>
                                            </div>
                                        @endif

                                        <div class="min-w-0 flex-1">
                                            

                                            @php
                                                $nameData = $category->name;

                                                if (is_string($nameData)) {
                                                    $nameData = json_decode($nameData, true) ?? [];
                                                }

                                                if (isset($nameData['en']) && is_string($nameData['en'])) {
                                                    $nested = json_decode($nameData['en'], true);
                                                    if (json_last_error() === JSON_ERROR_NONE && is_array($nested)) {
                                                        $nameData = $nested;
                                                    }
                                                }
                                            @endphp

                                            <div class="mt-0.5 space-y-0.5">
                                                <div class="font-medium text-gray-800 truncate">
                                                   <a
                                                        href="{{ route('admin.categories.show', $category) }}"
                                                        class="font-medium text-gray-800 truncate hover:text-primary-600 transition"
                                                    >
                                                        {{ $nameData['en'] ?? 'N/A' }}
                                                    </a>
                                                </div>
                                                @if(isset($nameData['am']))
                                                    <div dir="auto" class="text-xs text-gray-500 truncate">
                                                        {{ $nameData['am'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- QUESTIONS COUNT --}}
                                <td class="px-4 py-4 align-middle text-sm text-gray-600">
                                    <span class="inline-flex items-center rounded-md bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-500/10">
                                        {{ number_format($category->questions_count ?? 0) }}
                                    </span>
                                </td>

                                {{-- ORDER --}}
                                <td class="px-4 py-4 align-middle text-sm font-medium text-gray-700">
                                    {{ $category->sort_order ?? 0 }}
                                </td>

                                {{-- STATUS --}}
                                <td class="px-4 py-4 align-middle">
                                    @if($category->is_active)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                            Disabled
                                        </span>
                                    @endif
                                </td>

                                {{-- ACTIONS --}}
                                <td class="px-4 py-4 align-middle text-right" onclick="event.stopPropagation()">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <a
                                            href="{{ route('admin.categories.edit', $category) }}"
                                            class="rounded p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                            title="Edit"
                                        >
                                            <i class="ik ik-edit-2 text-base"></i>
                                        </a>

                                        <button
                                            type="button"
                                            onclick="deleteSingleCategory({{ $category->id }})"
                                            class="rounded p-1 text-gray-400 transition-colors hover:bg-rose-50 hover:text-rose-600"
                                            title="Delete"
                                        >
                                            <i class="ik ik-trash-2 text-base"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center">
                                    <div class="mx-auto flex max-w-xs flex-col items-center justify-center text-center">
                                        <i class="ik ik-grid text-4xl text-gray-300"></i>
                                        <p class="mt-2 text-sm font-medium text-gray-800">No categories found</p>
                                        <p class="mt-1 text-xs text-gray-500">Get started by creating your first category.</p>
                                        <a
                                            href="{{ route('admin.categories.create') }}"
                                            class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-700"
                                        >
                                            <i class="ik ik-plus"></i> Add Category
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if($categories->hasPages())
                <div class="border-t border-gray-100 p-4">
                    {{ $categories->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </form>

    {{-- DEDICATED HIDDEN FORM FOR SINGLE DELETE ACTIONS --}}
    <form id="single-delete-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    {{-- INCLUDE GLOBAL CONFIRMATION MODAL --}}
    @include('admin.components.confirm-modal')

</div>

<script>
    // Safe registration helper for Alpine Store
    function registerConfirmStore() {
        if (!window.Alpine) return;

        // Register or extend the store
        if (!Alpine.store('confirm') || typeof Alpine.store('confirm').ask !== 'function') {
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
                    this.title = options.title || 'Are you sure?';
                    this.message = options.message || 'This action cannot be undone.';
                    this.icon = options.icon || 'ik ik-trash-2';
                    this.tone = options.tone || 'danger';
                    this.cancelText = options.cancelText || 'Cancel';
                    this.confirmText = options.confirmText || 'Delete';
                    this.onConfirm = options.onConfirm || null;
                    this.show = true;
                },

                confirm() {
                    if (typeof this.onConfirm === 'function') {
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

    // Run store registration immediately if Alpine is ready, otherwise wait for init
    if (window.Alpine) {
        registerConfirmStore();
    } else {
        document.addEventListener('alpine:init', registerConfirmStore);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const selectAllCheckbox = document.getElementById('select-all');
        const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
        const bulkToolbar = document.getElementById('bulk-toolbar');
        const selectedCountSpan = document.getElementById('selected-count');

        function updateToolbar() {
            const checkedCount = document.querySelectorAll('.category-checkbox:checked').length;
            if (selectedCountSpan) selectedCountSpan.textContent = checkedCount;

            if (bulkToolbar) {
                if (checkedCount > 0) {
                    bulkToolbar.classList.remove('hidden');
                } else {
                    bulkToolbar.classList.add('hidden');
                }
            }

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = checkedCount > 0 && checkedCount === categoryCheckboxes.length;
            }
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                categoryCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateToolbar();
            });
        }

        categoryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateToolbar);
        });
    });

    // TRIGGER MODAL FOR BULK DELETE
    function confirmBulkDelete() {
        const count = document.querySelectorAll('.category-checkbox:checked').length;
        if (count === 0) return;

        if (window.Alpine && Alpine.store('confirm')) {
            Alpine.store('confirm').ask({
                title: 'Delete Selected Categories?',
                message: `Are you sure you want to permanently delete ${count} category(ies)? This action cannot be undone.`,
                icon: 'ik ik-trash-2',
                tone: 'danger',
                confirmText: 'Yes, Delete All',
                cancelText: 'Cancel',
                onConfirm: () => {
                    document.getElementById('bulk-form').submit();
                }
            });
        }
    }

    // TRIGGER MODAL FOR SINGLE DELETE
    function deleteSingleCategory(id) {
        if (window.Alpine && Alpine.store('confirm')) {
            Alpine.store('confirm').ask({
                title: 'Delete Category?',
                message: 'Are you sure you want to delete this category? This action cannot be undone.',
                icon: 'ik ik-trash-2',
                tone: 'danger',
                confirmText: 'Delete',
                cancelText: 'Cancel',
                onConfirm: () => {
                    const form = document.getElementById('single-delete-form');
                    form.action = `/admin/categories/${id}`;
                    form.submit();
                }
            });
        }
    }
</script>
@endsection