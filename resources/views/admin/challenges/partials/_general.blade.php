{{-- GENERAL INFORMATION --}}
<div 
    x-data="{
        imagePreview: '',
        existingImage: '{{ isset($challenge) && $challenge->thumbnail ? asset('storage/'.$challenge->thumbnail) : '' }}',
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                this.imagePreview = URL.createObjectURL(file);
            }
        },
        clearImage() {
            this.imagePreview = '';
            this.existingImage = '';
            if (this.$refs.thumbnailInput) {
                this.$refs.thumbnailInput.value = '';
            }
        }
    }"
    class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100"
>

    <div class="mb-5">
        <h2 class="font-semibold text-gray-800">
            General Information
        </h2>
        <p class="mt-1 text-xs text-gray-500">
            Basic challenge details and thumbnail.
        </p>
    </div>

    <div class="space-y-5">

        {{-- TITLE --}}
        <div>
            <label class="text-xs font-medium text-gray-500">
                Challenge Title *
            </label>
            <input
                type="text"
                name="title"
                value="{{ old('title', $challenge->title ?? '') }}"
                required
                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0 @error('title') border-red-500 @enderror"
                placeholder="Example: Ethiopian History Challenge"
            >
            @error('title')
                <p class="mt-1 text-xs text-red-500">
                    {{ $message }}
                </p>
            @enderror
        </div>

        {{-- SLUG --}}
        <div>
            <label class="text-xs font-medium text-gray-500">
                Slug
            </label>
            <input
                type="text"
                name="slug"
                value="{{ old('slug', $challenge->slug ?? '') }}"
                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
                placeholder="auto-generated-from-title"
            >
        </div>

        {{-- DESCRIPTION --}}
        <div>
            <label class="text-xs font-medium text-gray-500">
                Description
            </label>
            <textarea
                name="description"
                rows="4"
                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:ring-0"
                placeholder="Explain what this challenge is about..."
            >{{ old('description', $challenge->description ?? '') }}</textarea>
        </div>

        {{-- THUMBNAIL --}}
        <div>
            <label class="text-xs font-medium text-gray-500">
                Challenge Thumbnail
            </label>

            {{-- NEW IMAGE PREVIEW --}}
            <div
                x-show="imagePreview"
                x-cloak
                class="relative mt-2 mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
            >
                <p class="mb-2 text-xs font-medium text-gray-500">New Image Preview</p>
                <img
                    :src="imagePreview"
                    class="mx-auto max-h-64 rounded-lg object-contain"
                >
                <button
                    type="button"
                    @click="clearImage()"
                    class="absolute right-3 top-3 rounded-lg bg-red-500 px-3 py-1 text-xs font-medium text-white shadow-sm hover:bg-red-600 focus:outline-none"
                >
                    Remove
                </button>
            </div>

            {{-- EXISTING THUMBNAIL (EDIT MODE) --}}
            <div
                x-show="!imagePreview && existingImage"
                class="relative mt-2 mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4"
            >
                <p class="mb-2 text-xs font-medium text-gray-500">Current Thumbnail</p>
                <img
                    :src="existingImage"
                    class="mx-auto max-h-64 rounded-lg object-contain"
                >
                <button
                    type="button"
                    @click="clearImage()"
                    class="absolute right-3 top-3 rounded-lg bg-red-500 px-3 py-1 text-xs font-medium text-white shadow-sm hover:bg-red-600 focus:outline-none"
                >
                    Remove
                </button>
            </div>

            {{-- UPLOAD DROPZONE --}}
            <label
                x-show="!imagePreview && !existingImage"
                class="mt-2 flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 p-8 transition-colors hover:border-gray-300 hover:bg-gray-50"
            >
                <i class="ik ik-upload text-3xl text-gray-400"></i>

                <p class="mt-3 text-sm font-medium text-gray-700">
                    Upload thumbnail
                </p>

                <p class="text-xs text-gray-400">
                    PNG, JPG up to 5MB
                </p>

                <input
                    x-ref="thumbnailInput"
                    type="file"
                    name="thumbnail"
                    accept="image/*"
                    @change="previewImage"
                    class="hidden"
                >
            </label>

            @error('thumbnail')
                <p class="mt-1 text-xs text-red-500">
                    {{ $message }}
                </p>
            @enderror
        </div>

    </div>

</div>