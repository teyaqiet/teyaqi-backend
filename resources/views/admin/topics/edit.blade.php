@extends('admin.layouts.main')

@section('title', 'Edit Topic')

@section('content')

<div class="space-y-6">


    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">


        <div class="flex items-center gap-4">


            <a
                href="{{ route('admin.topics.index') }}"
                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
            >

                <i class="ik ik-arrow-left"></i>

            </a>





            <div>


                <h1 class="text-2xl font-bold text-gray-800">
                    Edit Topic
                </h1>


                <p class="mt-1 text-xs text-gray-500">
                    Update topic information and category assignment.
                </p>


            </div>



        </div>








        {{-- ACTIONS --}}
        <div class="flex items-center gap-3">


            <a
                href="{{ route('admin.topics.index') }}"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
            >
                Cancel
            </a>





            <button
                form="topic-form"
                type="submit"
                class="rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700"
            >

                Save Changes

            </button>



        </div>




    </div>









    {{-- FORM --}}
    <form
        id="topic-form"
        method="POST"
        action="{{ route('admin.topics.update',$topic) }}"
    >

        @csrf

        @method('PUT')



        @include(
            'admin.topics.partials._form'
        )



    </form>





</div>


@endsection