@php

$type = $field['type'] ?? 'text';

$key = $field['key'] ?? '';

$name = $field['name'] ?? '';

$value = settings($key);

$label = $field['label'] ?? '';

@endphp


{{-- TEXT --}}
@if($type === 'text')

<x-form.input
    name="{{ $name }}"
    label="{{ __($label) }}"
    value="{{ $value }}"
    icon="ik ik-box"
/>

@endif



{{-- EMAIL --}}
@if($type === 'email')

<x-form.input
    name="{{ $name }}"
    type="email"
    label="{{ __($label) }}"
    value="{{ $value }}"
    icon="ik ik-mail"
/>

@endif



{{-- TEXTAREA --}}
@if($type === 'textarea')

<x-form.textarea
    name="{{ $name }}"
    label="{{ __($label) }}"
>
    {{ $value }}
</x-form.textarea>

@endif



{{-- NUMBER --}}
@if($type === 'number')

<x-form.input
    name="{{ $name }}"
    type="number"
    label="{{ __($label) }}"
    value="{{ $value }}"
    icon="ik ik-hash"
/>

@endif



{{-- SELECT --}}
@if($type === 'select')

<x-form.select
    name="{{ $name }}"
    label="{{ __($label) }}"
>

@foreach($field['options'] ?? [] as $option)

<option 
    value="{{ $option['value'] }}"
    @selected($value == $option['value'])
>
    {{ $option['label'] }}
</option>

@endforeach

</x-form.select>

@endif



{{-- TOGGLE --}}
@if($type === 'toggle')

<div class="flex items-center justify-between gap-4 py-3.5">

    <div>
        <p class="text-sm font-medium text-gray-700">
            {{ __($label) }}
        </p>

        @if(isset($field['description']))
        <p class="text-xs text-gray-400">
            {{ __($field['description']) }}
        </p>
        @endif
    </div>


    <x-form.toggle
        name="{{ $name }}"
        :checked="$value"
    />

</div>

@endif

@if($type === 'file')

<div>

    <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ __($label) }}
    </label>


    @if($value)

        <div class="mb-3">

            <img 
                src="{{ asset('storage/'.$value) }}"
                class="h-16 rounded border"
            >

        </div>

    @endif


    <input
        type="file"
        name="{{ $name }}"
        class="block w-full text-sm text-gray-600
        border rounded-lg p-2"
    >

</div>

@endif