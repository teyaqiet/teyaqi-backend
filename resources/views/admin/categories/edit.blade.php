@extends('admin.layouts.main')

@section('title', 'Edit Category')

@section('content')
<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Edit Category
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Update Teyaqi question category.
            </p>
        </div>

        <a
            href="{{ route('admin.categories.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50"
        >
            <i class="ik ik-arrow-left"></i>
            Back
        </a>
    </div>

    {{-- VALIDATION ERRORS --}}
    @if($errors->any())
        <div class="rounded-xl bg-rose-50 p-4 text-sm text-rose-800 ring-1 ring-inset ring-rose-600/20" role="alert">
            <div class="flex items-center gap-2 font-semibold">
                <i class="ik ik-alert-circle text-lg text-rose-600"></i>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-rose-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM --}}
    <form
        method="POST"
        action="{{ route('admin.categories.update', $category) }}"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        {{-- BASIC INFORMATION --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h2 class="mb-5 text-sm font-semibold uppercase text-gray-700">
                Basic Information
            </h2>

            <div class="grid gap-5 md:grid-cols-2">
                {{-- ENGLISH NAME --}}
                <div>
                    <label for="name_en" class="mb-1 block text-sm font-medium text-gray-700">
                        English Name <span class="text-rose-500">*</span>
                    </label>

                    <input
                        id="name_en"
                        type="text"
                        name="name_en"
                        value="{{ old('name_en', $category->name['en'] ?? '') }}"
                        placeholder="Example: Science"
                        required
                        aria-required="true"
                        class="h-11 w-full rounded-lg border @error('name_en') border-rose-300 bg-rose-50/30 @else border-gray-200 @enderror px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >

                    @error('name_en')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- AMHARIC NAME --}}
                <div>
                    <label for="name_am" class="mb-1 block text-sm font-medium text-gray-700">
                        Amharic Name
                    </label>

                    <input
                        id="name_am"
                        type="text"
                        name="name_am"
                        value="{{ old('name_am', $category->name['am'] ?? '') }}"
                        placeholder="ምሳሌ፦ ሳይንስ"
                        dir="auto"
                        class="h-11 w-full rounded-lg border @error('name_am') border-rose-300 bg-rose-50/30 @else border-gray-200 @enderror px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >

                    @error('name_am')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                {{-- DESCRIPTION EN --}}
                <div>
                    <label for="description_en" class="mb-1 block text-sm font-medium text-gray-700">
                        English Description
                    </label>

                    <textarea
                        id="description_en"
                        name="description_en"
                        rows="4"
                        placeholder="Category description..."
                        class="w-full rounded-lg border @error('description_en') border-rose-300 bg-rose-50/30 @else border-gray-200 @enderror px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >{{ old('description_en', $category->description['en'] ?? '') }}</textarea>

                    @error('description_en')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- DESCRIPTION AM --}}
                <div>
                    <label for="description_am" class="mb-1 block text-sm font-medium text-gray-700">
                        Amharic Description
                    </label>

                    <textarea
                        id="description_am"
                        name="description_am"
                        rows="4"
                        dir="auto"
                        placeholder="የምድብ መግለጫ..."
                        class="w-full rounded-lg border @error('description_am') border-rose-300 bg-rose-50/30 @else border-gray-200 @enderror px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >{{ old('description_am', $category->description['am'] ?? '') }}</textarea>

                    @error('description_am')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- APPEARANCE --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h2 class="mb-5 text-sm font-semibold uppercase text-gray-700">
                Appearance
            </h2>

            <div class="grid gap-5 md:grid-cols-3">
                {{-- ICON --}}
                <div>
                    <label for="icon" class="mb-1 block text-sm font-medium text-gray-700">
                        Icon Class
                    </label>

                    <input
                        id="icon"
                        type="text"
                        name="icon"
                        value="{{ old('icon', $category->icon) }}"
                        placeholder="ik ik-star"
                        aria-describedby="icon-hint"
                        class="h-11 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >

                    <p id="icon-hint" class="mt-1 text-xs text-gray-400">
                        Example: <code class="rounded bg-gray-100 px-1 py-0.5 text-gray-600">ik ik-book</code>
                    </p>

                    @error('icon')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- COLOR --}}
                <div>
                    <label for="color" class="mb-1 block text-sm font-medium text-gray-700">
                        Color
                    </label>

                    <div class="flex items-center gap-2">
                        <input
                            id="color"
                            type="color"
                            name="color"
                            value="{{ old('color', $category->color ?? '#6366f1') }}"
                            class="h-11 w-full cursor-pointer rounded-lg border border-gray-200 @error('color') border-red-500 @enderror"
                    >
                       
                    </div>

                    @error('color')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- IMAGE --}}
                <div>
                    <label for="image_url" class="mb-1 block text-sm font-medium text-gray-700">
                        Image URL
                    </label>

                    <input
                        id="image_url"
                        type="url"
                        name="image_url"
                        value="{{ old('image_url', $category->image_url) }}"
                        placeholder="https://..."
                        class="h-11 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >

                    @error('image_url')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- SETTINGS --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
            <h2 class="mb-5 text-sm font-semibold uppercase text-gray-700">
                Settings
            </h2>

            <div class="grid gap-5 md:grid-cols-2">
                {{-- SORT ORDER --}}
                <div>
                    <label for="sort_order" class="mb-1 block text-sm font-medium text-gray-700">
                        Sort Order
                    </label>

                    <input
                        id="sort_order"
                        type="number"
                        name="sort_order"
                        value="{{ old('sort_order', $category->sort_order) }}"
                        min="0"
                        class="h-11 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                    >

                    @error('sort_order')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ACTIVE SWITCH --}}
                <div>
                    <label class="mb-3 block text-sm font-medium text-gray-700">
                        Status
                    </label>

                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >

                    <label class="inline-flex cursor-pointer items-center gap-3">
                        <input
                            id="is_active"
                            type="checkbox"
                            name="is_active"
                            value="1"
                            class="peer sr-only"
                            {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                        >

                        <div class="peer relative h-6 w-11 rounded-full bg-gray-200 transition-colors after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full"></div>

                        <span class="text-sm font-medium text-gray-700">
                            Active
                        </span>
                    </label>

                    @error('is_active')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-end gap-3">
            <a
                href="{{ route('admin.categories.index') }}"
                class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
                <i class="ik ik-save"></i>
                Update Category
            </button>
        </div>
    </form>
</div>
@endsection