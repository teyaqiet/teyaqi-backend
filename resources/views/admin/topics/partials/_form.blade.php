<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">


    <div class="mb-5">

        <h2 class="font-semibold text-gray-800">
            Topic Information
        </h2>

        <p class="mt-1 text-xs text-gray-500">
            Configure topic details and category assignment.
        </p>

    </div>





    <div class="space-y-6">





        {{-- CATEGORY --}}
        <div>


            <label class="text-xs font-medium text-gray-500">
                Category *
            </label>


            <select
                name="category_id"
                required
                class="mt-2 h-10 w-full rounded-lg border border-gray-200 px-3 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >

                <option value="">
                    Select Category
                </option>



                @foreach($categories as $category)

                    <option
                        value="{{ $category->id }}"
                        @selected(
                            old(
                                'category_id',
                                $topic->category_id ?? ''
                            ) == $category->id
                        )
                    >

                        {{ $category->name['en'] ?? $category->name }}

                    </option>


                @endforeach


            </select>



            @error('category_id')

                <p class="mt-1 text-xs text-red-500">
                    {{ $message }}
                </p>

            @enderror



        </div>









        {{-- TOPIC NAME --}}
        <div>


            <label class="text-xs font-medium text-gray-500">
                Topic Name *
            </label>



            <input
                type="text"
                name="name"
                value="{{ old('name', $topic->name ?? '') }}"
                placeholder="Example: Ethiopian History"
                required
                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >



            @error('name')

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
                value="{{ old('slug', $topic->slug ?? '') }}"
                placeholder="physics"
                class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >



            <p class="mt-1 text-xs text-gray-400">
                Leave empty to automatically generate from topic name.
            </p>



            @error('slug')

                <p class="mt-1 text-xs text-red-500">
                    {{ $message }}
                </p>

            @enderror



        </div>







    </div>





</div>