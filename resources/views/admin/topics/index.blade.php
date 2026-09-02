@extends('admin.layouts.main')

@section('title', 'Topics')

@section('content')

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

    {{-- FLASH MESSAGE --}}
    @if(session('success'))
        <div class="flex items-center justify-between rounded-xl bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
            <div class="flex items-center gap-2">
                <i class="ik ik-check-circle text-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button
                onclick="this.parentElement.remove()"
                class="text-emerald-600 hover:text-emerald-900"
            >
                &times;
            </button>
        </div>
    @endif

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Topics
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Manage question topics inside Teyaqi categories.
            </p>
        </div>

        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-gray-500">
                {{ number_format($topics->total()) }}
                {{ Str::plural('Topic', $topics->total()) }}
            </span>

            <a
                href="{{ route('admin.topics.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700"
            >
                <i class="ik ik-plus"></i>
                Add Topic
            </a>
        </div>
    </div>

    {{-- BULK ACTIONS BAR --}}
    <div 
        x-show="selectedIds.length > 0" 
        x-cloak 
        class="flex items-center justify-between rounded-xl bg-primary-50 p-4 ring-1 ring-primary-200/60"
    >
        <div class="flex items-center gap-2 text-sm font-medium text-primary-900">
            <i class="ik ik-check-square text-primary-600"></i>
            <span><strong x-text="selectedIds.length"></strong> topic(s) selected</span>
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

    {{-- FILTERS --}}
    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
        <form method="GET" action="{{ route('admin.topics.index') }}">
            <div class="flex flex-wrap items-end gap-3">
                
                {{-- SEARCH --}}
                <div class="min-w-[260px] flex-1">
                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        Search
                    </label>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search topic..."
                        class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm text-gray-800 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    >
                </div>

                {{-- CATEGORY --}}
                <div class="w-full sm:w-48">
                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        Category
                    </label>
                    <select
                        name="category"
                        class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm text-gray-800 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"
                    >
                        <option value="">
                            All Categories
                        </option>
                        @foreach($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(request('category') == $category->id)
                            >
                                {{ $category->name['en'] ?? $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- BUTTONS --}}
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="h-10 rounded-lg bg-gray-900 px-5 text-sm font-medium text-white transition-colors hover:bg-gray-800"
                    >
                        Filter
                    </button>

                    <a
                        href="{{ route('admin.topics.index') }}"
                        class="flex h-10 items-center rounded-lg border border-gray-200 px-4 text-sm text-gray-600 hover:bg-gray-50"
                    >
                        Reset
                    </a>
                </div>

            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="border-b border-gray-100 bg-gray-50">
                    <tr>
                        <th scope="col" class="w-10 px-4 py-4 text-center">
                            <input 
                                type="checkbox" 
                                x-model="selectAll" 
                                @change="toggleAll()" 
                                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                        </th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-gray-500">
                            Topic
                        </th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-gray-500">
                            Category
                        </th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-gray-500">
                            Slug
                        </th>
                        <th class="px-5 py-4 text-xs font-semibold uppercase text-gray-500">
                            Created
                        </th>
                        <th class="px-5 py-4 text-right text-xs font-semibold uppercase text-gray-500">
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                @forelse($topics as $topic)
                    @php
                        $topicName = is_array($topic->name) ? ($topic->name['en'] ?? reset($topic->name)) : $topic->name;
                    @endphp
                    <tr class="transition-colors hover:bg-gray-50/50">
                        <td class="w-10 px-4 py-4 text-center">
                            <input 
                                type="checkbox" 
                                value="{{ $topic->id }}" 
                                x-model="selectedIds" 
                                @change="updateSelectAll()" 
                                class="row-checkbox h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                        </td>

                        {{-- NAME --}}
                        <td class="px-5 py-4">
                            <a 
                                href="{{ route('admin.topics.show', $topic) }}"
                                class="font-medium text-gray-800 hover:text-primary-600 hover:underline"
                            >
                                {{ $topicName }}
                            </a>
                        </td>

                        {{-- CATEGORY --}}
                        <td class="px-5 py-4 text-sm text-gray-600">
                            {{ $topic->category?->name['en'] ?? $topic->category?->name ?? '-' }}
                        </td>

                        {{-- SLUG --}}
                        <td class="px-5 py-4 text-sm text-gray-500">
                            <span class="font-mono text-xs">{{ $topic->slug }}</span>
                        </td>

                        {{-- DATE --}}
                        <td class="px-5 py-4 text-sm text-gray-500">
                            {{ $topic->created_at ? $topic->created_at->format('M d, Y') : '-' }}
                        </td>

                        {{-- ACTIONS --}}
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex items-center gap-1">
                                {{-- VIEW --}}
                                <a
                                    href="{{ route('admin.topics.show', $topic) }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-primary-600"
                                    title="View"
                                >
                                    <i class="ik ik-eye"></i>
                                </a>

                                {{-- EDIT --}}
                                <a
                                    href="{{ route('admin.topics.edit', $topic) }}"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-amber-600"
                                    title="Edit"
                                >
                                    <i class="ik ik-edit-2"></i>
                                </a>

                                {{-- DELETE --}}
                                <button
                                    type="button"
                                    @click="confirmSingleDelete('{{ route('admin.topics.bulk-delete', $topic) }}', '{{ addslashes($topicName) }}')"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-rose-50 hover:text-rose-600"
                                    title="Delete"
                                >
                                    <i class="ik ik-trash-2"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center">
                            <i class="ik ik-layers text-4xl text-gray-300"></i>
                            <p class="mt-2 text-sm font-medium text-gray-800">
                                No topics found
                            </p>
                            <p class="text-xs text-gray-500">
                                Create your first topic.
                            </p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($topics->hasPages())
            <div class="border-t border-gray-100 p-4">
                {{ $topics->withQueryString()->links() }}
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
                                <span>Are you sure you want to delete <strong class="text-gray-800" x-text="selectedIds.length"></strong> selected topic(s)? This action cannot be undone.</span>
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
                        <form action="{{ route('admin.topics.bulk-delete') }}" method="POST" class="inline-block">
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