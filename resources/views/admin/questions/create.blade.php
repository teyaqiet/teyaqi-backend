@extends('admin.layouts.main')

@section('title', 'Create Question')

@section('content')

<div
    x-data="{
        imagePreview: '',
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                this.imagePreview = URL.createObjectURL(file);
            }
        },
        clearImage() {
            this.imagePreview = '';
            document.getElementById('imageInput').value = '';
        }
    }"
    class="space-y-6"
>
    {{-- INLINE VALIDATION ALERT --}}
    <x-alert />

    {{-- FORM --}}
    <form
        id="question-form"
        method="POST"
        action="{{ route('admin.questions.store') }}"
        enctype="multipart/form-data"
    >
        @csrf

        {{-- HEADER WITH ACTION BUTTONS --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <a
                    href="{{ route('admin.questions.index') }}"
                    class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
                >
                    <i class="ik ik-arrow-left"></i>
                </a>

                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        Create Question
                    </h1>
                    <p class="mt-1 text-xs text-gray-500">
                        Add a new question to Teyaqi.
                    </p>
                </div>
            </div>

            {{-- ACTION BUTTONS --}}
            <div class="flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('admin.questions.index') }}"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-2 text-sm font-semibold text-white transition-all hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                    Save Question
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- LEFT COLUMN --}}
            <div class="space-y-6 xl:col-span-2">

                {{-- QUESTION CONTENT --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">Question Content</h2>
                        <p class="mt-1 text-xs text-gray-500">English and Amharic question text.</p>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <label class="text-xs font-medium text-gray-500">English Question</label>
                            <textarea
                                name="question_text[en]"
                                rows="3"
                                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0 @error('question_text.en') border-red-500 @enderror"
                                placeholder="Enter question in English"
                            >{{ old('question_text.en') }}</textarea>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-gray-500">Amharic Question</label>
                            <textarea
                                name="question_text[am]"
                                rows="3"
                                dir="auto"
                                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0 @error('question_text.am') border-red-500 @enderror"
                                placeholder="ጥያቄውን በአማርኛ ያስገቡ"
                            >{{ old('question_text.am') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ANSWERS --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">Answer Options</h2>
                        <p class="mt-1 text-xs text-gray-500">Select the correct answer.</p>
                    </div>

                    <div class="space-y-4">
                        @foreach(['a','b','c','d'] as $option)
                            <div class="rounded-xl border border-gray-200 p-4 hover:bg-gray-50">
                                <div class="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        name="correct_answer"
                                        value="{{ $option }}"
                                        @checked(old('correct_answer', 'a') === $option)
                                        class="mt-2 h-4 w-4 text-green-600 focus:ring-0"
                                    >

                                    <div class="flex-1">
                                        <div class="mb-3">
                                            <span class="text-sm font-semibold text-gray-700">Option {{ strtoupper($option) }}</span>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="text-xs text-gray-500">English</label>
                                                <input
                                                    type="text"
                                                    name="option_{{ $option }}[en]"
                                                    value="{{ old('option_'.$option.'.en') }}"
                                                    class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                                    placeholder="English answer"
                                                >
                                            </div>

                                            <div>
                                                <label class="text-xs text-gray-500">Amharic</label>
                                                <input
                                                    type="text"
                                                    dir="auto"
                                                    name="option_{{ $option }}[am]"
                                                    value="{{ old('option_'.$option.'.am') }}"
                                                    class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                                    placeholder="የአማርኛ መልስ"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- IMAGE --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">Question Image</h2>
                        <p class="mt-1 text-xs text-gray-500">Optional image for the question.</p>
                    </div>

                    <div
                        x-show="imagePreview.length > 0"
                        x-cloak
                        class="relative mb-5 rounded-xl bg-gray-50 p-4"
                    >
                        <img :src="imagePreview" class="mx-auto max-h-72 rounded-lg object-contain" alt="Image preview">
                        <button
                            type="button"
                            @click="clearImage()"
                            class="absolute right-3 top-3 rounded-lg bg-red-500 px-3 py-1 text-xs font-medium text-white shadow-sm hover:bg-red-600"
                        >
                            Remove
                        </button>
                    </div>

                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 p-8 transition-colors hover:bg-gray-50">
                        <i class="ik ik-upload text-3xl text-gray-400"></i>
                        <p class="mt-3 text-sm font-medium text-gray-700">Upload image</p>
                        <p class="text-xs text-gray-400">PNG, JPG up to 5MB</p>

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

            </div>

            {{-- RIGHT COLUMN --}}
            <div class="space-y-6">

                {{-- BILINGUAL EXPLANATION --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <div class="mb-5">
                        <h2 class="font-semibold text-gray-800">Explanation</h2>
                        <p class="mt-1 text-xs text-gray-500">Provide explanations in English and Amharic.</p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-medium text-gray-500">English Explanation</label>
                            <textarea
                                name="explanation[en]"
                                rows="4"
                                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
                                placeholder="Explain the answer in English..."
                            >{{ old('explanation.en') }}</textarea>
                        </div>

                        <div>
                            <label class="text-xs font-medium text-gray-500">Amharic Explanation</label>
                            <textarea
                                name="explanation[am]"
                                rows="4"
                                dir="auto"
                                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
                                placeholder="ማብራሪያውን በአማርኛ ያስገቡ..."
                            >{{ old('explanation.am') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- SETTINGS --}}
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                    <h2 class="mb-5 font-semibold text-gray-800">Settings</h2>

                    <div class="space-y-5">
                        <div>
                            <label class="text-xs text-gray-500">Category</label>
                            <select
                                name="category_id"
                                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:ring-0"
                            >
                                <option value="">Select category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                        {{ $question->category->name['en'] ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500">Difficulty</label>
                            <select
                                name="difficulty"
                                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:ring-0"
                            >
                                @foreach(['easy', 'medium', 'hard'] as $level)
                                    <option value="{{ $level }}" @selected(old('difficulty', 'medium') === $level)>
                                        {{ ucfirst($level) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-xs text-gray-500">Status</label>
                            <select
                                name="is_active"
                                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:ring-0"
                            >
                                <option value="1" @selected(old('is_active', '1') == '1')>Active</option>
                                <option value="0" @selected(old('is_active') == '0')>Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>

@endsection