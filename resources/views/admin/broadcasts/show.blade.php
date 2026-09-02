@extends('admin.layouts.main')

@section('title', 'Broadcast Details')

@section('content')

<div class="space-y-6">

    {{-- ================================================================ --}}
    {{-- HEADER --}}
    {{-- ================================================================ --}}

    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

        <div>

            <div class="flex items-center gap-2 text-sm text-gray-500">

                <a
                    href="{{ route('admin.broadcasts.index') }}"
                    class="hover:text-primary-600"
                >
                    Broadcasts
                </a>

                <i class="ik ik-chevron-right text-sm"></i>

                <span>Details</span>

            </div>

            <h1 class="mt-2 text-2xl font-bold text-gray-800">
                {{ $broadcast->title }}
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Created {{ $broadcast->created_at?->format('M d, Y H:i') }}
            </p>

        </div>

        <div class="flex items-center gap-2">

            @if(in_array($broadcast->status, ['draft', 'scheduled', 'prepared']))

                <a
                    href="{{ route('admin.broadcasts.edit', $broadcast) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                >
                    <i class="ik ik-edit text-base"></i>
                    Edit
                </a>

            @endif

            <a
                href="{{ route('admin.broadcasts.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
            >
                <i class="ik ik-arrow-left text-base"></i>
                Back
            </a>

        </div>

    </div>


    {{-- ================================================================ --}}
    {{-- STATUS --}}
    {{-- ================================================================ --}}

    <div class="flex flex-wrap items-center gap-3">

        @switch($broadcast->status)

            @case('draft')

                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                    Draft
                </span>

                @break

            @case('prepared')

                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-indigo-600"></span>
                    Prepared
                </span>

                @break

            @case('scheduled')

                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                    Scheduled
                </span>

                @break

            @case('sending')

                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    Sending
                </span>

                @break

            @case('completed')

                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                    Completed
                </span>

                @break

            @case('failed')

                <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                    Failed
                </span>

                @break

            @default

                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600">
                    {{ ucfirst($broadcast->status) }}
                </span>

        @endswitch

        <span class="text-gray-300">•</span>

        <span class="text-sm text-gray-500">
            {{ ucfirst($broadcast->channel) }}
        </span>

        <span class="text-gray-300">•</span>

        <span class="text-sm text-gray-500">
            {{ ucfirst($broadcast->type) }}
        </span>

    </div>


    {{-- ================================================================ --}}
    {{-- RECIPIENT STATISTICS --}}
    {{-- ================================================================ --}}

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- TOTAL --}}

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Total Recipients
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-800">
                        {{ number_format($broadcast->recipients_count) }}
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600">
                    <i class="ik ik-users text-lg"></i>
                </div>

            </div>

        </div>


        {{-- PENDING --}}

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Pending
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-800">
                        {{ number_format($broadcast->pending_recipients_count) }}
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                    <i class="ik ik-clock text-lg"></i>
                </div>

            </div>

        </div>


        {{-- SENT --}}

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Sent
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-800">
                        {{ number_format($broadcast->sent_recipients_count) }}
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                    <i class="ik ik-check-circle text-lg"></i>
                </div>

            </div>

        </div>


        {{-- FAILED --}}

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex items-center justify-between">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                        Failed
                    </p>

                    <p class="mt-2 text-2xl font-bold text-gray-800">
                        {{ number_format($broadcast->failed_recipients_count) }}
                    </p>

                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                    <i class="ik ik-alert-circle text-lg"></i>
                </div>

            </div>

        </div>

    </div>


    {{-- ================================================================ --}}
    {{-- DELIVERY PROGRESS --}}
    {{-- ================================================================ --}}

    @if($broadcast->recipients_count > 0)

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <div class="flex items-center gap-2">

                        <h2 class="text-sm font-semibold text-gray-800">
                            Delivery Progress
                        </h2>

                        @if($broadcast->status === 'sending')

                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700">
                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-amber-500"></span>
                                Sending
                            </span>

                        @elseif($broadcast->status === 'completed')

                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">
                                <i class="ik ik-check text-[10px]"></i>
                                Complete
                            </span>

                        @elseif($broadcast->status === 'failed')

                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700">
                                <i class="ik ik-alert-circle text-[10px]"></i>
                                Failed
                            </span>

                        @endif

                    </div>

                    <p class="mt-0.5 text-xs text-gray-500">

                        {{ number_format($processed) }}
                        of
                        {{ number_format($total) }}
                        recipients processed.

                    </p>

                </div>

                <div class="text-right">

                    <p class="text-2xl font-bold text-gray-800">
                        {{ number_format($progress, 1) }}%
                    </p>

                </div>

            </div>


            {{-- PROGRESS BAR --}}

            <div class="mt-4">

                <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">

                    <div
                        class="h-full rounded-full bg-primary-600 transition-all duration-500"
                        style="width: {{ min($progress, 100) }}%"
                    ></div>

                </div>

            </div>


            {{-- PROGRESS DETAILS --}}

            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">

                <div>

                    <p class="text-xs text-gray-400">
                        Processed
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-700">
                        {{ number_format($processed) }}
                    </p>

                </div>

                <div>

                    <p class="text-xs text-gray-400">
                        Pending
                    </p>

                    <p class="mt-1 text-sm font-semibold text-amber-600">
                        {{ number_format($broadcast->pending_recipients_count) }}
                    </p>

                </div>

                <div>

                    <p class="text-xs text-gray-400">
                        Sent
                    </p>

                    <p class="mt-1 text-sm font-semibold text-emerald-600">
                        {{ number_format($broadcast->sent_recipients_count) }}
                    </p>

                </div>

                <div>

                    <p class="text-xs text-gray-400">
                        Failed
                    </p>

                    <p class="mt-1 text-sm font-semibold text-rose-600">
                        {{ number_format($broadcast->failed_recipients_count) }}
                    </p>

                </div>

            </div>

        </div>

    @endif


    {{-- ================================================================ --}}
    {{-- MAIN GRID --}}
    {{-- ================================================================ --}}

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">


        {{-- ============================================================ --}}
        {{-- LEFT --}}
        {{-- ============================================================ --}}

        <div class="space-y-6 lg:col-span-2">


            {{-- MESSAGE --}}

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

                <div class="border-b border-gray-100 px-5 py-4">

                    <h2 class="text-sm font-semibold text-gray-800">
                        Message
                    </h2>

                </div>

                <div class="p-6">

                    <div class="whitespace-pre-wrap rounded-lg bg-gray-50 p-5 text-sm leading-6 text-gray-700">{{ $broadcast->message }}</div>

                </div>

            </div>


            {{-- BUTTONS --}}

            @if(!empty($broadcast->buttons))

                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

                    <div class="border-b border-gray-100 px-5 py-4">

                        <h2 class="text-sm font-semibold text-gray-800">
                            Message Buttons
                        </h2>

                        <p class="mt-0.5 text-xs text-gray-500">
                            Buttons included with this Telegram message.
                        </p>

                    </div>

                    <div class="p-5">

                        <div class="space-y-3">

                            @foreach($broadcast->buttons as $button)

                                <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">

                                    <div class="flex min-w-0 items-center gap-3">

                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-primary-600 ring-1 ring-gray-100">

                                            @if(($button['type'] ?? null) === 'web_app')

                                                <i class="ik ik-grid text-sm"></i>

                                            @else

                                                <i class="ik ik-external-link text-sm"></i>

                                            @endif

                                        </div>

                                        <div class="min-w-0">

                                            <p class="text-sm font-semibold text-gray-700">
                                                {{ $button['text'] ?? 'Button' }}
                                            </p>

                                            <p class="mt-0.5 max-w-md truncate text-xs text-gray-400">
                                                {{ $button['url'] ?? '' }}
                                            </p>

                                        </div>

                                    </div>

                                    <span class="ml-4 shrink-0 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-gray-500 ring-1 ring-gray-100">
                                        {{ ($button['type'] ?? 'url') === 'web_app' ? 'Web App' : 'URL' }}
                                    </span>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            @endif


            {{-- RECIPIENTS --}}

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

                <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <h2 class="text-sm font-semibold text-gray-800">
                            Recipients
                        </h2>

                        <p class="mt-0.5 text-xs text-gray-500">
                            Players selected for this broadcast.
                        </p>

                    </div>

                    @if($broadcast->recipients_count > 0)

                        <span class="text-xs font-medium text-gray-400">
                            {{ number_format($broadcast->recipients_count) }} total
                        </span>

                    @endif

                </div>


                @if($recipients->count())

                    <div class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-gray-100">

                            <thead class="bg-gray-50">

                                <tr>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                                        Player
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                                        Status
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                                        Attempts
                                    </th>

                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-400">
                                        Sent At
                                    </th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-gray-100 bg-white">

                                @foreach($recipients as $recipient)

                                    <tr class="transition hover:bg-gray-50">

                                        {{-- PLAYER --}}

                                        <td class="whitespace-nowrap px-5 py-4">

                                            <div class="flex items-center gap-3">

                                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-600">
                                                    {{ strtoupper(substr($recipient->user?->name ?? 'U', 0, 1)) }}
                                                </div>

                                                <div>

                                                    <p class="text-sm font-semibold text-gray-700">
                                                        {{ $recipient->user?->name ?? 'Unknown Player' }}
                                                    </p>

                                                    <p class="text-xs text-gray-400">
                                                        ID: {{ $recipient->user_id }}
                                                    </p>

                                                </div>

                                            </div>

                                        </td>


                                        {{-- STATUS --}}

                                        <td class="whitespace-nowrap px-5 py-4">

                                            @switch($recipient->status)

                                                @case('pending')

                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">

                                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>

                                                        Pending

                                                    </span>

                                                    @break

                                                @case('sent')

                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">

                                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>

                                                        Sent

                                                    </span>

                                                    @break

                                                @case('failed')

                                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">

                                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>

                                                        Failed

                                                    </span>

                                                    @break

                                                @default

                                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                                        {{ ucfirst($recipient->status) }}
                                                    </span>

                                            @endswitch

                                        </td>


                                        {{-- ATTEMPTS --}}

                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                            {{ $recipient->attempts ?? 0 }}
                                        </td>


                                        {{-- SENT AT --}}

                                        <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">

                                            @if($recipient->sent_at)

                                                {{ $recipient->sent_at->format('M d, Y H:i') }}

                                            @else

                                                —

                                            @endif

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>


                    @if($recipients->hasPages())

                        <div class="border-t border-gray-100 px-5 py-4">
                            {{ $recipients->links() }}
                        </div>

                    @endif

                @else

                    <div class="px-5 py-12 text-center">

                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                            <i class="ik ik-users text-xl"></i>
                        </div>

                        <h3 class="mt-4 text-sm font-semibold text-gray-700">
                            No recipients yet
                        </h3>

                        <p class="mt-1 text-xs text-gray-400">
                            Prepare this broadcast to generate the recipient list.
                        </p>

                    </div>

                @endif

            </div>


            {{-- FILTERS --}}

            @if($broadcast->audience_type === 'filtered' && !empty($broadcast->filters))

                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

                    <div class="border-b border-gray-100 px-5 py-4">

                        <h2 class="text-sm font-semibold text-gray-800">
                            Audience Filters
                        </h2>

                    </div>

                    <div class="p-5">

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                            @foreach($broadcast->filters as $key => $value)

                                <div class="rounded-lg bg-gray-50 p-4">

                                    <p class="text-xs font-medium uppercase text-gray-400">
                                        {{ str_replace('_', ' ', $key) }}
                                    </p>

                                    <p class="mt-1 text-sm font-semibold text-gray-700">

                                        @if(is_bool($value))

                                            {{ $value ? 'Yes' : 'No' }}

                                        @else

                                            {{ $value }}

                                        @endif

                                    </p>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </div>

            @endif

        </div>


        {{-- ============================================================ --}}
        {{-- SIDEBAR --}}
        {{-- ============================================================ --}}

        <div class="space-y-6">


            {{-- AUDIENCE --}}

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

                <div class="border-b border-gray-100 px-5 py-4">

                    <h2 class="text-sm font-semibold text-gray-800">
                        Audience
                    </h2>

                </div>

                <div class="space-y-4 p-5">

                    @php

                        $audienceLabels = [
                            'all' => 'All Players',
                            'active' => 'Active Players',
                            'inactive' => 'Inactive Players',
                            'filtered' => 'Filtered Players',
                        ];

                    @endphp

                    <div>

                        <p class="text-xs font-medium uppercase text-gray-400">
                            Target
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-700">
                            {{ $audienceLabels[$broadcast->audience_type] ?? ucfirst($broadcast->audience_type) }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase text-gray-400">
                            Recipients
                        </p>

                        <p class="mt-1 text-2xl font-bold text-gray-800">
                            {{ number_format($broadcast->recipients_count) }}
                        </p>

                    </div>

                </div>

            </div>


            {{-- DELIVERY --}}

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-100">

                <div class="border-b border-gray-100 px-5 py-4">

                    <h2 class="text-sm font-semibold text-gray-800">
                        Delivery
                    </h2>

                </div>

                <div class="space-y-4 p-5">

                    <div>

                        <p class="text-xs font-medium uppercase text-gray-400">
                            Channel
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-700">
                            {{ ucfirst($broadcast->channel) }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase text-gray-400">
                            Scheduled At
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-700">

                            @if($broadcast->scheduled_at)

                                {{ $broadcast->scheduled_at->format('M d, Y H:i') }}

                            @else

                                Not scheduled

                            @endif

                        </p>

                    </div>


                    <div>

                        <p class="text-xs font-medium uppercase text-gray-400">
                            Sent At
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-700">

                            @if($broadcast->sent_at)

                                {{ $broadcast->sent_at->format('M d, Y H:i') }}

                            @else

                                Not sent

                            @endif

                        </p>

                    </div>

                </div>

            </div>


            {{-- ======================================================== --}}
            {{-- ACTIONS --}}
            {{-- ======================================================== --}}

            @if($broadcast->status === 'draft')

                {{-- PREPARE --}}

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

                    <div class="mb-4 flex items-center gap-3">

                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <i class="ik ik-users text-base"></i>
                        </div>

                        <div>

                            <p class="text-sm font-semibold text-gray-800">
                                Prepare Audience
                            </p>

                            <p class="text-xs text-gray-400">
                                Generate the recipient list.
                            </p>

                        </div>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('admin.broadcasts.prepare', $broadcast) }}"
                        onsubmit="return confirm('Prepare recipients for this broadcast?');"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700"
                        >
                            <i class="ik ik-users text-base"></i>
                            Prepare Broadcast
                        </button>

                    </form>


                    <p class="mt-2 text-center text-xs text-gray-400">
                        This will find all matching Telegram players and prepare them as recipients.
                    </p>

                </div>


            @elseif($broadcast->status === 'scheduled')

                {{-- SCHEDULED --}}

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

                    <div class="flex items-center gap-3">

                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                            <i class="ik ik-calendar text-base"></i>
                        </div>

                        <div>

                            <p class="text-sm font-semibold text-gray-800">
                                Broadcast Scheduled
                            </p>

                            <p class="text-xs text-gray-400">
                                {{ $broadcast->scheduled_at?->format('M d, Y H:i') }}
                            </p>

                        </div>

                    </div>

                    <p class="mt-4 text-xs leading-5 text-gray-400">
                        This broadcast is scheduled for delivery. You can still edit it before it is prepared or sent.
                    </p>

                </div>


            @elseif($broadcast->status === 'prepared')

                {{-- SEND --}}

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

                    <div class="mb-4 flex items-center gap-3">

                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <i class="ik ik-check-circle text-base"></i>
                        </div>

                        <div>

                            <p class="text-sm font-semibold text-gray-800">
                                Broadcast Ready
                            </p>

                            <p class="text-xs text-gray-400">
                                {{ number_format($broadcast->recipients_count) }} recipients prepared.
                            </p>

                        </div>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('admin.broadcasts.send', $broadcast) }}"
                        onsubmit="return confirm('Are you sure you want to send this broadcast to {{ number_format($broadcast->recipients_count) }} recipients?');"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        >
                            <i class="ik ik-send text-base"></i>
                            Send Broadcast
                        </button>

                    </form>


                    <p class="mt-2 text-center text-xs text-gray-400">
                        This will send the broadcast to all prepared recipients.
                    </p>

                </div>


            @elseif($broadcast->status === 'sending')

                {{-- SENDING --}}

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

                    <div class="flex items-center gap-3">

                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600">

                            <i class="ik ik-loader animate-spin text-base"></i>

                        </div>

                        <div>

                            <p class="text-sm font-semibold text-gray-800">
                                Broadcast Sending
                            </p>

                            <p class="text-xs text-gray-400">
                                Messages are currently being delivered.
                            </p>

                        </div>

                    </div>


                    <div class="mt-4 rounded-lg bg-amber-50 p-3">

                        <div class="flex items-start gap-2">

                            <i class="ik ik-info mt-0.5 text-sm text-amber-600"></i>

                            <p class="text-xs leading-5 text-amber-700">
                                The delivery progress above will update as recipients are processed.
                            </p>

                        </div>

                    </div>

                </div>


            @elseif($broadcast->status === 'completed')

                {{-- COMPLETED --}}

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

                    <div class="flex items-center gap-3">

                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                            <i class="ik ik-check-circle text-base"></i>
                        </div>

                        <div>

                            <p class="text-sm font-semibold text-gray-800">
                                Broadcast Completed
                            </p>

                            <p class="text-xs text-gray-400">
                                Delivery has finished.
                            </p>

                        </div>

                    </div>


                    @if($broadcast->failed_recipients_count > 0)

                        <div class="mt-4 rounded-lg bg-amber-50 p-3">

                            <div class="flex items-start gap-2">

                                <i class="ik ik-alert-triangle mt-0.5 text-sm text-amber-600"></i>

                                <div>

                                    <p class="text-xs font-semibold text-amber-700">
                                        Some deliveries failed
                                    </p>

                                    <p class="mt-0.5 text-xs leading-5 text-amber-600">
                                        {{ number_format($broadcast->failed_recipients_count) }}
                                        recipients could not receive the message.
                                    </p>

                                </div>

                            </div>

                        </div>


                        <form
                            method="POST"
                            action="{{ route('admin.broadcasts.retry-failed', $broadcast) }}"
                            class="mt-4"
                            onsubmit="return confirm('Retry {{ number_format($broadcast->failed_recipients_count) }} failed recipients?');"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                            >
                                <i class="ik ik-refresh-cw text-base"></i>
                                Retry Failed
                            </button>

                        </form>

                        <p class="mt-2 text-center text-xs text-gray-400">
                            Only failed recipients will be retried.
                        </p>

                    @endif

                </div>


            @elseif($broadcast->status === 'failed')

                {{-- FAILED --}}

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-100">

                    <div class="flex items-center gap-3">

                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                            <i class="ik ik-alert-circle text-base"></i>
                        </div>

                        <div>

                            <p class="text-sm font-semibold text-gray-800">
                                Broadcast Failed
                            </p>

                            <p class="text-xs text-gray-400">

                                {{ number_format($broadcast->failed_recipients_count) }}
                                recipients failed to receive the message.

                            </p>

                        </div>

                    </div>


                    @if($broadcast->failed_recipients_count > 0)

                        <form
                            method="POST"
                            action="{{ route('admin.broadcasts.retry-failed', $broadcast) }}"
                            class="mt-4"
                            onsubmit="return confirm('Retry {{ number_format($broadcast->failed_recipients_count) }} failed recipients?');"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                            >
                                <i class="ik ik-refresh-cw text-base"></i>
                                Retry Failed
                            </button>

                        </form>

                        <p class="mt-2 text-center text-xs text-gray-400">
                            Only failed recipients will be retried.
                        </p>

                    @endif

                </div>

            @endif

        </div>

    </div>

</div>

@endsection