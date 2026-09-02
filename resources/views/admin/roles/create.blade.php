@extends('admin.layouts.main')


@section('title','Create Role')


@section('content')


<div class="space-y-6">


<div>

<h1 class="text-2xl font-bold text-gray-800">
    Create Role
</h1>


<p class="mt-1 text-sm text-gray-500">
    Create a new admin role and assign permissions.
</p>


</div>



@include(
    'admin.roles._form',
    [
        'action'=>route('admin.roles.store'),
        'method'=>'POST',
        'button'=>'Create Role'
    ]
)


</div>


@endsection