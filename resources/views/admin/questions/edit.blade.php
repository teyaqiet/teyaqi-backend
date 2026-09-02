@extends('admin.layouts.main')

@section('title','Edit Question')

@section('content')

<div
    x-data="{
        imagePreview: '{{ $question->image_url ? asset('storage/'.$question->getRawOriginal('image_url')) : '' }}',
        removeImage: false,

        previewImage(event){
            const file = event.target.files[0];

            if(file){
                this.imagePreview = URL.createObjectURL(file);
                this.removeImage = false;
            }
        },

        clearImage(){
            this.imagePreview = '';
            this.removeImage = true;
            document.getElementById('imageInput').value = '';
        }
    }"
    class="space-y-6"
>

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a
                href="{{ route('admin.questions.show',$question) }}"
                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
            >
                <i class="ik ik-arrow-left"></i>
            </a>

            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    Edit Question
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Update question content, answers and settings.
                </p>
            </div>
        </div>

        <div class="flex gap-3">
            <a
                href="{{ route('admin.questions.show',$question) }}"
                class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
            >
                Cancel
            </a>

            <button
                form="question-form"
                class="rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700"
            >
                Save Question
            </button>
        </div>
    </div>

    <form
        id="question-form"
        method="POST"
        action="{{ route('admin.questions.update',$question) }}"
        enctype="multipart/form-data"
    >
        @csrf
        @method('PUT')

        <input
            type="hidden"
            name="remove_image"
            x-bind:value="removeImage ? 1 : 0"
        />

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- LEFT CONTENT --}}
            <div class="space-y-6 xl:col-span-2">

                {{-- QUESTION --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">
                            Question Content
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">
                            Multilingual question text.
                        </p>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="text-xs font-medium text-gray-500">
                                English Question
                            </label>
                            <textarea
                                name="question_text[en]"
                                rows="3"
                                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
                            >{{ is_array($question->question_text) ? ($question->question_text['en'] ?? '') : $question->question_text }}</textarea>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-gray-500">
                                Amharic Question
                            </label>
                            <textarea
                                name="question_text[am]"
                                rows="3"
                                dir="auto"
                                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
                            >{{ is_array($question->question_text) ? ($question->question_text['am'] ?? '') : '' }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ANSWERS --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">
                            Answer Options
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">
                            Manage choices, translations and correct answer.
                        </p>
                    </div>

                    <div class="space-y-4">
                        @foreach(['a','b','c','d'] as $option)
                            @php
                                $value = $question->{'option_'.$option};

                                if(!is_array($value)){
                                    $value = [
                                        'en' => $value,
                                        'am' => ''
                                    ];
                                }

                                $isCorrect = strtolower($question->correct_answer) == $option;
                            @endphp

                            <div class="rounded-xl border p-4 transition {{ $isCorrect ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-white hover:bg-gray-50' }}">
                                <div class="flex items-start gap-3">
                                    {{-- Correct Answer --}}
                                    <div class="pt-1">
                                        <input
                                            type="radio"
                                            name="correct_answer"
                                            value="{{ $option }}"
                                            @checked($isCorrect)
                                            class="h-4 w-4 text-green-600"
                                        >
                                    </div>

                                    <div class="flex-1">
                                        <div class="mb-3 flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-700">
                                                Option {{ strtoupper($option) }}
                                            </span>

                                            @if($isCorrect)
                                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                    Correct Answer
                                                </span>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            {{-- English --}}
                                            <div>
                                                <label class="text-xs font-medium text-gray-500">
                                                    English
                                                </label>
                                                <input
                                                    type="text"
                                                    name="option_{{ $option }}[en]"
                                                    value="{{ $value['en'] ?? '' }}"
                                                    class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
                                                >
                                            </div>

                                            {{-- Amharic --}}
                                            <div>
                                                <label class="text-xs font-medium text-gray-500">
                                                    Amharic
                                                </label>
                                                <input
                                                    type="text"
                                                    dir="auto"
                                                    name="option_{{ $option }}[am]"
                                                    value="{{ $value['am'] ?? '' }}"
                                                    class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- IMAGE UPLOAD --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">
                            Question Image
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">
                            Upload or replace image.
                        </p>
                    </div>

                    <div
                        x-show="imagePreview"
                        class="relative mb-5 rounded-xl bg-gray-50 p-4"
                    >
                        <img
                            :src="imagePreview"
                            class="mx-auto max-h-72 rounded-lg object-contain"
                        >

                        <button
                            type="button"
                            @click="clearImage()"
                            class="absolute right-3 top-3 rounded-lg bg-red-500 px-3 py-1 text-xs font-semibold text-white hover:bg-red-600"
                        >
                            Remove
                        </button>
                    </div>

                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 p-8 text-center hover:bg-gray-50">
                        <i class="ik ik-upload text-3xl text-gray-400"></i>

                        <p class="mt-3 text-sm font-medium text-gray-700">
                            Drop image here or click to upload
                        </p>

                        <p class="mt-1 text-xs text-gray-400">
                            PNG, JPG up to 5MB
                        </p>

                        <input
                            id="imageInput"
                            type="file"
                            name="image"
                            accept="image/*"
                            @change="previewImage"
                            class="hidden"
                        >
                    </label>
                </div>

            </div> {{-- END LEFT CONTENT --}}

            {{-- RIGHT SIDEBAR --}}
            <div class="space-y-6">

                {{-- EXPLANATION --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">
                            Explanation
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">
                            Explain why this answer is correct.
                        </p>
                    </div>

                    @php
                        $explanation = $question->explanation;
                        if (!is_array($explanation)) {
                            $explanation = ['en' => $explanation ?? '', 'am' => ''];
                        }
                    @endphp

                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-medium text-gray-500">
                                English Explanation
                            </label>
                            <textarea
                                name="explanation[en]"
                                rows="3"
                                class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
                                placeholder="Example: Addis Ababa has 11 sub-cities..."
                            >{{ $explanation['en'] ?? '' }}</textarea>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-gray-500">
                                Amharic Explanation
                            </label>
                            <textarea
                                name="explanation[am]"
                                rows="3"
                                dir="auto"
                                class="mt-1 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
                            >{{ $explanation['am'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- SETTINGS --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">
                            Settings
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">
                            Question configuration.
                        </p>
                    </div>

                    <div class="space-y-5">
                        {{-- CATEGORY --}}
                        <div>
                            <label class="text-xs font-medium text-gray-500">
                                Category
                            </label>
                            <select
                                name="category_id"
                                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm"
                            >
                                @foreach($categories as $category)
                                    <option
                                        value="{{ $category->id }}"
                                        @selected($question->category_id == $category->id)
                                    >
                                        {{ $question->category->name['en'] ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- DIFFICULTY --}}
                        <div>
                            <label class="text-xs font-medium text-gray-500">
                                Difficulty
                            </label>
                            <select
                                name="difficulty"
                                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm"
                            >
                                @foreach(['easy','medium','hard'] as $level)
                                    <option
                                        value="{{ $level }}"
                                        @selected($question->difficulty == $level)
                                    >
                                        {{ ucfirst($level) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- STATUS --}}
                        <div>
                            <label class="text-xs font-medium text-gray-500">
                                Status
                            </label>
                            <select
                                name="is_active"
                                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm"
                            >
                                <option value="1" @selected($question->is_active)>
                                    Active
                                </option>
                                <option value="0" @selected(!$question->is_active)>
                                    Disabled
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- PERFORMANCE --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">
                            Performance
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">
                            Question analytics.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">
                                Times Shown
                            </span>
                            <span class="font-semibold text-gray-800">
                                {{ number_format($question->times_shown ?? 0) }}
                            </span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">
                                Correct Answers
                            </span>
                            <span class="font-semibold text-green-600">
                                {{ number_format($question->times_correct ?? 0) }}
                            </span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">
                                Difficulty Score
                            </span>
                            <span class="font-semibold text-primary-600">
                                {{ number_format($question->difficulty_score ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- METADATA --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">
                            Metadata
                        </h2>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">
                                Question ID
                            </p>
                            <p class="font-medium text-gray-700">
                                #{{ $question->id }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400">
                                Created
                            </p>
                            <p class="font-medium text-gray-700">
                                {{ $question->created_at?->format('M d, Y') }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-400">
                                Last Updated
                            </p>
                            <p class="font-medium text-gray-700">
                                {{ $question->updated_at?->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                </div>

            </div> {{-- END RIGHT SIDEBAR --}}

        </div> {{-- END GRID --}}
    </form>

</div> {{-- END ALPINE ROOT DIV --}}

@endsection