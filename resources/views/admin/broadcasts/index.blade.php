@extends('admin.layouts.main')

@section('title', 'Broadcasts')

@section('content')

{{-- FLASH MESSAGES --}}
@if(session('success'))
    <div class="mb-5 flex items-center justify-between rounded-xl bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20">
        <div class="flex items-center gap-2">
            <i class="ik ik-check-circle text-lg text-emerald-600"></i>
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

@if(session('error'))
    <div class="mb-5 flex items-center justify-between rounded-xl bg-rose-50 p-4 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-600/20">
        <div class="flex items-center gap-2">
            <i class="ik ik-alert-circle text-lg text-rose-600"></i>
            <span>{{ session('error') }}</span>
        </div>

        <button
            onclick="this.parentElement.remove()"
            class="text-rose-600 hover:text-rose-900"
        >
            &times;
        </button>
    </div>
@endif


{{-- HEADER --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            Broadcasts
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Manage messages sent to Teyaqi players.
        </p>
    </div>

    <div class="flex items-center gap-4">

        <span class="text-sm font-medium text-gray-500">
            {{ number_format($broadcasts->total()) }}
            {{ Str::plural('Broadcast', $broadcasts->total()) }}
        </span>

        <a
            href="{{ route('admin.broadcasts.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
        >
            <i class="ik ik-plus text-base"></i>
            Create Broadcast
        </a>

    </div>

</div>


{{-- BROADCASTS TABLE --}}
<div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

    <div class="overflow-x-auto">

        <table class="w-full text-left">

            <thead class="border-b border-gray-100 bg-gray-50/50">

                <tr>

                    <th class="px-5 py-3.5 text-xs font-semibold uppercase text-gray-500">
                        Broadcast
                    </th>

                    <th class="px-5 py-3.5 text-xs font-semibold uppercase text-gray-500">
                        Audience
                    </th>

                    <th class="px-5 py-3.5 text-xs font-semibold uppercase text-gray-500">
                        Channel
                    </th>

                    <th class="px-5 py-3.5 text-xs font-semibold uppercase text-gray-500">
                        Status
                    </th>

                    <th class="px-5 py-3.5 text-xs font-semibold uppercase text-gray-500">
                        Scheduled
                    </th>

                    <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase text-gray-500">
                        Actions
                    </th>

                </tr>

            </thead>


            <tbody class="divide-y divide-gray-100">

                @forelse($broadcasts as $broadcast)

                    <tr class="group transition-colors hover:bg-gray-50/80">

                        {{-- BROADCAST --}}
                        <td class="px-5 py-4 align-middle">

                            <div class="flex items-center gap-3">

                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                                    <i class="ik ik-send text-lg"></i>
                                </div>

                                <div class="min-w-0">

                                    <div class="truncate font-medium text-gray-800">
                                        {{ $broadcast->title }}
                                    </div>

                                    <div class="mt-0.5 max-w-md truncate text-xs text-gray-500">
                                        {{ $broadcast->message }}
                                    </div>

                                    <div class="mt-1 text-xs text-gray-400">
                                        Created {{ $broadcast->created_at?->format('M d, Y H:i') }}
                                    </div>

                                </div>

                            </div>

                        </td>


                        {{-- AUDIENCE --}}
                        <td class="px-5 py-4 align-middle">

                            @php
                                $audienceLabels = [
                                    'all' => 'All Players',
                                    'active' => 'Active Players',
                                    'inactive' => 'Inactive Players',
                                    'filtered' => 'Filtered Players',
                                ];
                            @endphp

                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-500/10">
                                {{ $audienceLabels[$broadcast->audience_type] ?? ucfirst($broadcast->audience_type) }}
                            </span>

                        </td>


                        {{-- CHANNEL --}}
                        <td class="px-5 py-4 align-middle">

                            <div class="flex items-center gap-2 text-sm text-gray-600">

                                <i class="ik ik-send text-base text-gray-400"></i>

                                <span>
                                    {{ ucfirst($broadcast->channel) }}
                                </span>

                            </div>

                        </td>


                        {{-- STATUS --}}
                        <td class="px-5 py-4 align-middle">

                            @switch($broadcast->status)

                                @case('draft')

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                        Draft
                                    </span>

                                    @break


                                @case('scheduled')

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                                        Scheduled
                                    </span>

                                    @break


                                @case('sending')

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        Sending
                                    </span>

                                    @break


                                @case('completed')

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                        Completed
                                    </span>

                                    @break


                                @case('failed')

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                        Failed
                                    </span>

                                    @break

                            @endswitch

                        </td>


                        {{-- SCHEDULED --}}
                        <td class="px-5 py-4 align-middle text-sm text-gray-600">

                            @if($broadcast->scheduled_at)

                                <div>
                                    {{ $broadcast->scheduled_at->format('M d, Y') }}
                                </div>

                                <div class="mt-0.5 text-xs text-gray-400">
                                    {{ $broadcast->scheduled_at->format('H:i') }}
                                </div>

                            @else

                                <span class="text-gray-400">
                                    —
                                </span>

                            @endif

                        </td>


                        {{-- ACTIONS --}}
                        <td class="px-5 py-4 align-middle text-right">

                            <div class="inline-flex items-center justify-end gap-1">

                                <a
                                    href="{{ route('admin.broadcasts.show', $broadcast) }}"
                                    class="rounded p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                    title="View"
                                >
                                    <i class="ik ik-eye text-base"></i>
                                </a>

                                @if($broadcast->status === 'draft')

                                    <a
                                        href="{{ route('admin.broadcasts.edit', $broadcast) }}"
                                        class="rounded p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                        title="Edit"
                                    >
                                        <i class="ik ik-edit-2 text-base"></i>
                                    </a>

                                @endif

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="py-16 text-center">

                            <div class="mx-auto flex max-w-xs flex-col items-center justify-center text-center">

                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gray-100">
                                    <i class="ik ik-send text-2xl text-gray-400"></i>
                                </div>

                                <p class="mt-3 text-sm font-semibold text-gray-800">
                                    No broadcasts found
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    Create your first broadcast to communicate with Teyaqi players.
                                </p>

                                <a
                                    href="{{ route('admin.broadcasts.create') }}"
                                    class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-700"
                                >
                                    <i class="ik ik-plus"></i>
                                    Create Broadcast
                                </a>

                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- PAGINATION --}}
    @if($broadcasts->hasPages())

        <div class="border-t border-gray-100 p-4">
            {{ $broadcasts->withQueryString()->links() }}
        </div>

    @endif

</div>

@endsection