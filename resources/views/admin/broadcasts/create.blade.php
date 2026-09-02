@extends('admin.layouts.main')

@section('title', 'Create Broadcast')

@section('content')

    {{-- ============================================================
        FLASH MESSAGES
    ============================================================= --}}

    @if(session('success'))
        <div class="mb-6 flex items-center justify-between rounded-xl bg-emerald-50 p-4 text-sm font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/20">

            <div class="flex items-center gap-2">
                <i class="ik ik-check-circle text-lg text-emerald-600"></i>

                <span>
                    {{ session('success') }}
                </span>
            </div>

            <button
                type="button"
                onclick="this.parentElement.remove()"
                class="text-emerald-600 transition hover:text-emerald-900"
                aria-label="Dismiss success message"
            >
                &times;
            </button>

        </div>
    @endif


    @if(session('error'))
        <div class="mb-6 flex items-center justify-between rounded-xl bg-rose-50 p-4 text-sm font-medium text-rose-800 ring-1 ring-inset ring-rose-600/20">

            <div class="flex items-center gap-2">
                <i class="ik ik-alert-circle text-lg text-rose-600"></i>

                <span>
                    {{ session('error') }}
                </span>
            </div>

            <button
                type="button"
                onclick="this.parentElement.remove()"
                class="text-rose-600 transition hover:text-rose-900"
                aria-label="Dismiss error message"
            >
                &times;
            </button>

        </div>
    @endif


    @if($errors->any())
        <div class="mb-6 rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-inset ring-rose-600/20">

            <div class="flex items-center gap-2 font-semibold">
                <i class="ik ik-alert-circle text-lg text-rose-600"></i>

                <span>
                    Please fix the following errors:
                </span>
            </div>

            <ul class="mt-2 list-disc space-y-1 pl-6">

                @foreach($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach

            </ul>

        </div>
    @endif


    {{-- ============================================================
        HEADER
    ============================================================= --}}

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            {{-- Breadcrumb --}}

            <div class="flex items-center gap-2">

                <a
                    href="{{ route('admin.broadcasts.index') }}"
                    class="text-sm font-medium text-gray-400 transition hover:text-primary-600"
                >
                    Broadcasts
                </a>

                <i class="ik ik-chevron-right text-sm text-gray-400"></i>

                <span class="text-sm font-medium text-gray-500">
                    Create
                </span>

            </div>


            {{-- Page title --}}

            <h1 class="mt-2 text-2xl font-bold text-gray-800">
                Create Broadcast
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Create a message and choose which Teyaqi players should receive it.
            </p>

        </div>


        {{-- Back button --}}

        <a
            href="{{ route('admin.broadcasts.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition-all hover:bg-gray-50 hover:text-gray-800"
        >
            <i class="ik ik-arrow-left text-base"></i>

            Back to Broadcasts
        </a>

    </div>


    {{-- ============================================================
        BROADCAST FORM
    ============================================================= --}}

    @include('admin.broadcasts._form')

@endsection