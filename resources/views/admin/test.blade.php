@extends('admin.layouts.main')

@section('title','Layout Test')

@section('content')


<x-page-header
    title="Layout Test"
    icon="ik ik-grid"
/>

<div class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">


<div class="rounded-xl bg-red-100 p-10">
LEFT
</div>


<div class="rounded-xl bg-blue-100 p-10">
CENTER
</div>


<div class="rounded-xl bg-green-100 p-10">
RIGHT
</div>


</div>


@endsection