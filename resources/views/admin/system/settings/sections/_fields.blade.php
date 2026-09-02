<div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

    @foreach($settings as $field)

        <x-settings.field
            :field="$field"
        />

    @endforeach

</div>