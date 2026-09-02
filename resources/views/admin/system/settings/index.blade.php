@extends('admin.layouts.main')

@section('title', 'Settings')

@section('content')


<x-settings-layout active="{{ $activeGroup }}">

<form 
    method="POST"
    action="{{ route('admin.settings.update') }}"
    class="space-y-6"
>

@csrf


<input 
    type="hidden"
    name="group"
    value="{{ $activeGroup }}"
>



@include(
    'admin.system.settings.sections.'.$activeGroup,
    [
        'settings'=>$settings
    ]
)



<x-save-bar />

</form>


</x-settings-layout>


@endsection