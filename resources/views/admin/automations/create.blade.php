@extends('admin.layouts.main')

@section('title', 'Create Automation — Teyaqi')

@section('content')

<div class="w-full space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-xl font-bold tracking-tight text-gray-900">
                Create Automation
            </h1>

            <p class="mt-1 text-xs text-gray-500">
                Create an automation workflow and configure its basic settings.
            </p>
        </div>

        <a
            href="{{ route('admin.automations.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200 transition hover:bg-gray-50"
        >
            <i class="ik ik-arrow-left text-sm"></i>
            <span>Back to Automations</span>
        </a>

    </div>


    {{-- Validation Errors --}}
    @if($errors->any())

        <div class="rounded-2xl border border-rose-100 bg-rose-50/60 p-5 shadow-sm">

            <div class="flex items-start gap-3">

                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                    <i class="ik ik-alert-circle text-sm"></i>
                </div>

                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-rose-700">
                        Please fix the following errors
                    </h3>

                    <ul class="mt-2 space-y-1 text-xs font-medium text-rose-600">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>

            </div>

        </div>

    @endif


    {{-- Form Card --}}
    <div class="w-full rounded-2xl border border-gray-100 bg-white p-6 shadow-xl shadow-black/5 sm:p-8">

        <form
            method="POST"
            action="{{ route('admin.automations.store') }}"
            class="space-y-6"
        >

            @csrf


            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                {{-- Name --}}
                <div class="md:col-span-2">

                    <label
                        for="name"
                        class="block text-xs font-semibold uppercase tracking-wider text-gray-600"
                    >
                        Automation Name
                    </label>

                    <div class="relative mt-1.5">

                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="ik ik-zap"></i>
                        </span>

                        <input
                            type="text"
                            name="name"
                            id="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            class="w-full rounded-xl border
                                @error('name')
                                    border-rose-300 bg-rose-50/30
                                @else
                                    border-gray-200
                                @enderror
                                py-2.5 pl-9 pr-3 text-sm text-gray-800
                                placeholder-gray-400 transition
                                focus:border-indigo-500 focus:outline-none
                                focus:ring-2 focus:ring-indigo-500/20"
                            placeholder="Streak Milestone Notification"
                        >

                    </div>

                    <p class="mt-1.5 text-xs text-gray-400">
                        Give the automation a clear and descriptive name.
                    </p>

                    @error('name')
                        <p class="mt-1 text-xs font-medium text-rose-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Description --}}
                <div class="md:col-span-2">

                    <label
                        for="description"
                        class="block text-xs font-semibold uppercase tracking-wider text-gray-600"
                    >
                        Description
                    </label>

                    <div class="relative mt-1.5">

                        <span class="pointer-events-none absolute left-0 top-0 flex items-center pl-3 pt-3 text-gray-400">
                            <i class="ik ik-file-text"></i>
                        </span>

                        <textarea
                            name="description"
                            id="description"
                            rows="4"
                            class="w-full rounded-xl border
                                @error('description')
                                    border-rose-300 bg-rose-50/30
                                @else
                                    border-gray-200
                                @enderror
                                py-2.5 pl-9 pr-3 text-sm text-gray-800
                                placeholder-gray-400 transition
                                focus:border-indigo-500 focus:outline-none
                                focus:ring-2 focus:ring-indigo-500/20"
                            placeholder="Send a Telegram message when a player reaches a streak milestone."
                        >{{ old('description') }}</textarea>

                    </div>

                    @error('description')
                        <p class="mt-1 text-xs font-medium text-rose-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Status --}}
                <div>

                    <label
                        for="status"
                        class="block text-xs font-semibold uppercase tracking-wider text-gray-600"
                    >
                        Status
                    </label>

                    <div class="relative mt-1.5">

                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="ik ik-toggle-right"></i>
                        </span>

                        <select
                            name="status"
                            id="status"
                            required
                            class="w-full appearance-none rounded-xl border
                                @error('status')
                                    border-rose-300 bg-rose-50/30
                                @else
                                    border-gray-200
                                @enderror
                                bg-white py-2.5 pl-9 pr-9 text-sm text-gray-800
                                transition focus:border-indigo-500
                                focus:outline-none
                                focus:ring-2 focus:ring-indigo-500/20"
                        >

                            <option
                                value="draft"
                                @selected(old('status', 'draft') === 'draft')
                            >
                                Draft
                            </option>

                            <option
                                value="active"
                                @selected(old('status') === 'active')
                            >
                                Active
                            </option>

                            <option
                                value="paused"
                                @selected(old('status') === 'paused')
                            >
                                Paused
                            </option>

                        </select>

                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                            <i class="ik ik-chevron-down"></i>
                        </span>

                    </div>

                    <p class="mt-1.5 text-xs text-gray-400">
                        New automations normally start as Draft.
                    </p>

                    @error('status')
                        <p class="mt-1 text-xs font-medium text-rose-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Version --}}
                <div>

                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600">
                        Version
                    </label>

                    <div class="relative mt-1.5">

                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="ik ik-git-branch"></i>
                        </span>

                        <div class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-3 text-sm text-gray-500">
                            Version 1
                        </div>

                    </div>

                    <p class="mt-1.5 text-xs text-gray-400">
                        Versions are managed automatically.
                    </p>

                </div>

            </div>


            {{-- Information --}}
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">

                <div class="flex items-start gap-3">

                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <i class="ik ik-info text-sm"></i>
                    </div>

                    <div>

                        <h3 class="text-xs font-semibold text-gray-800">
                            Next step
                        </h3>

                        <p class="mt-1 text-xs leading-5 text-gray-500">
                            After creating the automation, you will be taken to the Builder
                            where you can add triggers, conditions, actions, and connections.
                        </p>

                    </div>

                </div>

            </div>


            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">

                <a
                    href="{{ route('admin.automations.index') }}"
                    class="rounded-xl px-4 py-2.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-100"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-indigo-700"
                >
                    <i class="ik ik-zap text-sm"></i>
                    <span>Create Automation</span>
                </button>

            </div>

        </form>

    </div>

</div>

@endsection