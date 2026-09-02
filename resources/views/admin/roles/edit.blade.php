@extends('admin.layouts.main')

@section('title', 'Edit Role: ' . $role->name)

@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            Edit Role: {{ $role->name }}
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Update role details and adjust assigned permissions.
        </p>
    </div>

    @include(
        'admin.roles._form',
        [
            'action' => route('admin.roles.update', $role),
            'method' => 'PUT',
            'button' => 'Update Role'
        ]
    )

</div>

@endsection