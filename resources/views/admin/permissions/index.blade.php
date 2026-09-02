@extends('admin.layouts.main')

@section('title', 'Permissions')

@section('content')
<div class="space-y-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Permissions</h1>
            <p class="mt-1 text-xs text-gray-500">View system privileges and access control rules registered across modules.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200/60">
                <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                <span>{{ $permissions->flatten()->count() }} total permissions</span>
            </div>
        </div>
    </div>

    {{-- Permissions Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        @forelse($permissions as $module => $items)

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200/60 flex flex-col justify-between">

            <div>
                {{-- Card Header --}}
                <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 capitalize tracking-wide">
                            {{ str_replace('_', ' ', $module) }}
                        </h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ $items->count() }} {{ Str::plural('permission', $items->count()) }}
                        </p>
                    </div>

                    <span class="inline-flex items-center rounded-lg bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 uppercase tracking-wider">
                        {{ $module }}
                    </span>
                </div>

                {{-- Permission List --}}
                <div class="divide-y divide-gray-100">
                    @foreach($items as $permission)
                    <div class="flex items-center justify-between px-6 py-3.5 hover:bg-gray-50/60 transition group">

                        <div class="min-w-0 pr-4">
                            <p class="text-sm font-semibold text-gray-900 capitalize truncate">
                                {{ str_replace([':', '_', '-'], ' ', $permission->name) }}
                            </p>
                            <p class="text-xs text-gray-400 font-mono truncate mt-0.5">
                                {{ $permission->name }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            {{-- Delete --}}
                            <form
                                method="POST"
                                action="{{ route('admin.permissions.destroy', $permission) }}"
                                onsubmit="return confirm('Are you sure you want to delete the permission \'{{ $permission->name }}\'?');"
                                class="inline-block"
                            >
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="rounded-lg p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600 transition cursor-pointer"
                                    title="Delete Permission"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

        @empty

        <div class="col-span-full overflow-hidden rounded-2xl bg-white p-12 text-center shadow-sm ring-1 ring-gray-200/60">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400 mb-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-900">No permissions found</p>
            <p class="mt-1 text-xs text-gray-500">Run your database seeders to populate system permissions.</p>
        </div>

        @endforelse

    </div>

</div>
@endsection