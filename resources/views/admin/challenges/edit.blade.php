@extends('admin.layouts.main')

@section('title', 'Edit Challenge')

@section('content')
<div class="space-y-6">

    <x-alert />

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <a href="{{ route('admin.challenges.index') }}" class="hover:text-gray-700">Challenges</a>
                <span>/</span>
                <span class="text-gray-900">Edit Challenge</span>
            </div>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">
                Edit Challenge: {{ $challenge->title['en'] ?? $challenge->title }}
            </h1>
        </div>

        <div class="flex items-center gap-3">
            <a
                href="{{ route('admin.challenges.index') }}"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
            >
                Cancel
            </a>
            <button
                type="submit"
                form="challenge-form"
                class="rounded-lg bg-gray-900 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2"
            >
                Save Changes
            </button>
        </div>
    </div>

    {{-- FORM --}}
    <form
        id="challenge-form"
        method="POST"
        action="{{ route('admin.challenges.update', $challenge) }}"
        enctype="multipart/form-data"
    >
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- MAIN CONTENT --}}
            <div class="space-y-6 xl:col-span-2">
                @include('admin.challenges.partials._general', ['challenge' => $challenge])
                @include('admin.challenges.partials._rules', ['challenge' => $challenge])
                @include('admin.challenges.partials._questions', ['challenge' => $challenge])
            </div>

            {{-- SIDEBAR --}}
            <div class="space-y-6">
                @include('admin.challenges.partials._settings', ['challenge' => $challenge])
                @include('admin.challenges.partials._rewards', ['challenge' => $challenge])
                @include('admin.challenges.partials._schedule', ['challenge' => $challenge])
            </div>

        </div>
    </form>

</div>
@endsection