@extends('admin.layouts.main')

@section('title', 'Create Challenge')

@section('content')

<div class="space-y-6">


    {{-- ALERTS --}}
    <x-alert />



    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">


        <div class="flex items-center gap-4">


            <a
                href="{{ route('admin.challenges.index') }}"
                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
            >
                <i class="ik ik-arrow-left"></i>
            </a>



            <div>


                <h1 class="text-2xl font-bold text-gray-800">
                    Create Challenge
                </h1>


                <p class="mt-1 text-xs text-gray-500">
                    Configure challenge settings, rules, questions and rewards for Teyaqi.
                </p>


            </div>


        </div>





        {{-- ACTIONS --}}
        <div class="flex items-center gap-3">


            <a
                href="{{ route('admin.challenges.index') }}"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
            >
                Cancel
            </a>




            <button
                form="challenge-form"
                type="submit"
                class="rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700 focus:ring-2 focus:ring-primary-500"
            >
                Save Challenge
            </button>


        </div>


    </div>









    {{-- FORM --}}
    <form
        id="challenge-form"
        method="POST"
        action="{{ route('admin.challenges.store') }}"
        enctype="multipart/form-data"
    >

        @csrf





        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">





            {{-- MAIN CONTENT --}}
            <div class="space-y-6 xl:col-span-2">


                {{-- GENERAL --}}
                @include(
                    'admin.challenges.partials._general'
                )



                {{-- RULES --}}
                @include(
                    'admin.challenges.partials._rules'
                )



                {{-- QUESTIONS --}}
                @include(
                    'admin.challenges.partials._questions'
                )


            </div>









            {{-- SIDEBAR --}}
            <div class="space-y-6">


                {{-- SETTINGS --}}
                @include(
                    'admin.challenges.partials._settings'
                )



                {{-- REWARDS --}}
                @include(
                    'admin.challenges.partials._rewards'
                )



                {{-- SCHEDULE --}}
                @include(
                    'admin.challenges.partials._schedule'
                )



            </div>



        </div>





    </form>




</div>


@endsection