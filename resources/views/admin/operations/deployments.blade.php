@extends('admin.layouts.main')

@section('content')
<div
    x-data="operationsDeployments()"
    x-init="init()"
    class="space-y-6"
>
    {{-- Header --}}
    @include('admin.operations.deployments.partials.header')

    {{-- Overview --}}
    @include('admin.operations.deployments.partials.overview-stats')

    {{-- Active Deployment --}}
    @include('admin.operations.deployments.partials.active-deployment')

    {{-- Pipeline --}}
    @include('admin.operations.deployments.partials.pipeline')

    {{-- Deployment History --}}
    @include('admin.operations.deployments.partials.history')

    {{-- Modals --}}
    @include('admin.operations.deployments.partials.deploy-modal')
    @include('admin.operations.deployments.partials.preflight-modal')
    @include('admin.operations.deployments.partials.details-modal')
    @include('admin.operations.deployments.partials.message-modal')
</div>

{{-- Alpine / Deployment Logic --}}
@include('admin.operations.deployments.script')
@endsection