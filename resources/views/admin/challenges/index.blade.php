@extends('admin.layouts.main')

@section('title', 'Challenges')

@section('content')
@php
    // Helper to safely parse normal, JSON string, or double-encoded JSON attributes
    $parseJsonField = function ($field) {
        if (is_array($field)) {
            return $field;
        }

        if (is_string($field)) {
            $decoded = json_decode($field, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : ['en' => $field];
        }

        return [];
    };
@endphp

<div class="space-y-6" x-data="{
    selectedIds: [],
    selectAll: false,
    deleteModalOpen: false,
    deleteUrl: '',
    deleteTitle: '',
    isBulkDelete: false,

    toggleAll() {
        if (this.selectAll) {
            this.selectedIds = Array.from(document.querySelectorAll('.row-checkbox')).map(cb => cb.value);
        } else {
            this.selectedIds = [];
        }
    },

    updateSelectAll() {
        const total = document.querySelectorAll('.row-checkbox').length;
        this.selectAll = total > 0 && this.selectedIds.length === total;
    },

    confirmSingleDelete(url, title) {
        this.isBulkDelete = false;
        this.deleteUrl = url;
        this.deleteTitle = title;
        this.deleteModalOpen = true;
    },

    confirmBulkDelete() {
        if (this.selectedIds.length === 0) return;
        this.isBulkDelete = true;
        this.deleteModalOpen = true;
    }
}">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Challenges
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Manage and filter active or draft challenges.
            </p>
        </div>

        <div>
            <a
                href="{{ route('admin.challenges.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
                <i class="ik ik-plus"></i>
                Create Challenge
            </a>
        </div>
    </div>

    {{-- BULK ACTIONS BAR --}}
    <div 
        x-show="selectedIds.length > 0" 
        x-cloak 
        class="flex items-center justify-between rounded-xl bg-primary-50 p-4 ring-1 ring-primary-200/60"
    >
        <div class="flex items-center gap-2 text-sm text-primary-900 font-medium">
            <i class="ik ik-check-square text-primary-600"></i>
            <span><strong x-text="selectedIds.length"></strong> challenge(s) selected</span>
        </div>

        <button
            type="button"
            @click="confirmBulkDelete()"
            class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-red-700 focus:outline-none"
        >
            <i class="ik ik-trash-2"></i>
            Delete Selected
        </button>
    </div>

    {{-- FILTERS & CONTENT CARD --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        
        {{-- FIXED FILTER ROW --}}
        <div class="border-b border-gray-100 p-4 sm:p-6">
            <form method="GET" action="{{ route('admin.challenges.index') }}">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-12 sm:items-center">
                    
                    {{-- SEARCH INPUT --}}
                    <div class="{{ request()->hasAny(['search', 'status']) ? 'sm:col-span-5 md:col-span-6' : 'sm:col-span-7 md:col-span-8' }}">
                        <div class="relative flex items-center">
                            <i class="ik ik-search absolute left-3.5 text-gray-400"></i>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search challenge by title..."
                                class="h-10 w-full rounded-lg border border-gray-200 pl-10 pr-3.5 text-sm text-gray-800 placeholder-gray-400 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                            />
                        </div>
                    </div>

                    {{-- STATUS SELECT --}}
                    <div class="sm:col-span-4 md:col-span-2">
                        <select
                            name="status"
                            class="h-10 w-full rounded-lg border border-gray-200 px-3.5 text-sm text-gray-800 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                        >
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>

                    {{-- FILTER BUTTON --}}
                    <div class="sm:col-span-3 md:col-span-2 flex items-center gap-2">
                        <button
                            type="submit"
                            class="h-10 w-full inline-flex items-center justify-center gap-2 rounded-lg bg-gray-800 px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-gray-900 focus:outline-none"
                        >
                            <i class="ik ik-filter text-xs"></i>
                            Filter
                        </button>

                        {{-- CLEAR BUTTON (ONLY WHEN FILTERS ACTIVE) --}}
                        @if(request()->hasAny(['search', 'status']))
                            <a
                                href="{{ route('admin.challenges.index') }}"
                                class="h-10 inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50 shrink-0"
                                title="Clear Filters"
                            >
                                <i class="ik ik-x"></i>
                            </a>
                        @endif
                    </div>

                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-100 bg-gray-50/50">
                    <tr>
                        <th scope="col" class="w-10 px-4 py-3.5 text-center">
                            <input 
                                type="checkbox" 
                                x-model="selectAll" 
                                @change="toggleAll()" 
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Title
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Category
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Questions
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            XP Reward
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($challenges as $challenge)
                        @php
                            // Handle category name parsing
                            $catNameData = $challenge->category ? $parseJsonField($challenge->category->name) : [];
                            $catNameEn = !empty($catNameData['en']) ? $catNameData['en'] : ($catNameData['am'] ?? null);

                            // Title parsing
                            $titleData = $parseJsonField($challenge->title);
                            $titleText = is_array($titleData) ? ($titleData['en'] ?? reset($titleData)) : $challenge->title;

                            // Status resolution
                            $statusStr = strtolower(is_object($challenge->status) ? ($challenge->status->value ?? '') : (string) $challenge->status);
                        @endphp

                        <tr class="transition-colors hover:bg-gray-50/50">
                            <td class="w-10 px-4 py-4 text-center">
                                <input 
                                    type="checkbox" 
                                    value="{{ $challenge->id }}" 
                                    x-model="selectedIds" 
                                    @change="updateSelectAll()" 
                                    class="row-checkbox h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                />
                            </td>

                            <td class="px-6 py-4 font-medium text-gray-800">
                                <a
                                    href="{{ route('admin.challenges.show', $challenge) }}"
                                    class="text-primary-600 hover:text-primary-700 hover:underline"
                                >
                                    {{ $titleText }}
                                </a>
                            </td>

                            <td class="px-6 py-4 text-gray-600">
                                @if($catNameEn)
                                    <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                        {{ $catNameEn }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-gray-600">
                                <span class="font-mono text-xs font-semibold">
                                    {{ $challenge->question_count ?? $challenge->questions_count ?? 0 }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-gray-600">
                                <span class="inline-flex items-center gap-1 font-mono text-xs font-semibold text-amber-600">
                                    <i class="ik ik-award text-amber-500"></i>
                                    {{ number_format($challenge->reward_xp ?? 0) }} XP
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                @if($statusStr === 'active')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                                        {{ ucfirst($statusStr ?: 'Draft') }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a
                                        href="{{ route('admin.challenges.show', $challenge) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-primary-600"
                                        title="View"
                                    >
                                        <i class="ik ik-eye"></i>
                                    </a>
                                    <a
                                        href="{{ route('admin.challenges.edit', $challenge) }}"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-amber-600"
                                        title="Edit"
                                    >
                                        <i class="ik ik-edit"></i>
                                    </a>
                                    <button
                                        type="button"
                                        @click="confirmSingleDelete('{{ route('admin.challenges.bulk-delete', $challenge) }}', '{{ addslashes($titleText) }}')"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600"
                                        title="Delete"
                                    >
                                        <i class="ik ik-trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                <i class="ik ik-inbox mb-2 block text-3xl text-gray-300"></i>
                                No challenges found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($challenges->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $challenges->withQueryString()->links() }}
            </div>
        @endif

    </div>

    {{-- DELETE CONFIRMATION MODAL --}}
    <div 
        x-show="deleteModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto"
    >
        <div class="flex min-h-screen items-center justify-center p-4 text-center">
            {{-- Backdrop --}}
            <div 
                x-show="deleteModalOpen" 
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="deleteModalOpen = false" 
                class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"
            ></div>

            {{-- Dialog Box --}}
            <div 
                x-show="deleteModalOpen" 
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all"
            >
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <i class="ik ik-alert-triangle text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">
                            Confirm Deletion
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <template x-if="!isBulkDelete">
                                <span>Are you sure you want to delete <strong class="text-gray-800" x-text="deleteTitle"></strong>? This action cannot be undone.</span>
                            </template>
                            <template x-if="isBulkDelete">
                                <span>Are you sure you want to delete <strong class="text-gray-800" x-text="selectedIds.length"></strong> selected challenge(s)? This action cannot be undone.</span>
                            </template>
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="deleteModalOpen = false"
                        class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
                    >
                        Cancel
                    </button>

                    {{-- Single Delete Form --}}
                    <template x-if="!isBulkDelete">
                        <form :action="deleteUrl" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-700"
                            >
                                Delete
                            </button>
                        </form>
                    </template>

                    {{-- Bulk Delete Form --}}
                    <template x-if="isBulkDelete">
                        <form action="{{ route('admin.challenges.bulk-delete') }}" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <template x-for="id in selectedIds" :key="id">
                                <input type="hidden" name="ids[]" :value="id">
                            </template>
                            <button
                                type="submit"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-700"
                            >
                                Delete All
                            </button>
                        </form>
                    </template>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection