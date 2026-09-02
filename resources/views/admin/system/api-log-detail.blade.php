@extends('admin.layouts.main')

@section('title', __('API Log Details'))

@section('content')
    <x-page-header title="{{ __('API Log Details') }}" subtitle="{{ $apiLog->method }} - {{ $apiLog->url }}" icon="ik ik-code"
                    :breadcrumbs="['Home' => route('admin.dashboard'), 'System' => null, 'API Logs' => route('admin.system.api-logs.index'), 'Details' => null]" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <x-card title="Request Information">
            <ul class="space-y-3 text-sm text-gray-600">
                <li><strong class="text-gray-800">Method:</strong> <span class="font-mono">{{ $apiLog->method }}</span></li>
                <li><strong class="text-gray-800">Endpoint:</strong> <span class="font-mono break-all">{{ $apiLog->url }}</span></li>
                <li><strong class="text-gray-800">IP Address:</strong> <span class="font-mono">{{ $apiLog->ip_address }}</span></li>
                <li><strong class="text-gray-800">Status Code:</strong> <span class="font-mono">{{ $apiLog->status_code }}</span></li>
                <li><strong class="text-gray-800">Response Time:</strong> <span class="font-mono">{{ $apiLog->response_time }} ms</span></li>
                <li><strong class="text-gray-800">Causer:</strong> {{ $apiLog->causer->name ?? 'Guest' }}</li>
                <li><strong class="text-gray-800">Timestamp:</strong> {{ $apiLog->created_at->format('Y-m-d H:i:s') }}</li>
            </ul>
        </x-card>

        <x-card title="Request Payload">
            <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-64"><code>@json($apiLog->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)</code></pre>
        </x-card>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <x-card title="Response Body">
            <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-96"><code>@json($apiLog->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)</code></pre>
        </x-card>
    </div>
@endsection