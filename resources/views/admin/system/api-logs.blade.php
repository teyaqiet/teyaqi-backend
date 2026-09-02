@extends('admin.layouts.main')

@section('title', __('API Logs'))

@section('content')
    <x-page-header title="{{ __('API Logs') }}" subtitle="{{ __('Monitor incoming API requests, payloads, and response codes') }}" icon="ik ik-code"
                    :breadcrumbs="['Home' => route('admin.dashboard'), 'System' => null, 'API Logs' => null]" />

    <x-card no-padding>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                        <th class="px-6 py-4">{{ __('Method') }}</th>
                        <th class="px-6 py-4">{{ __('URL / Endpoint') }}</th>
                        <th class="px-6 py-4">{{ __('Status') }}</th>
                        <th class="px-6 py-4">{{ __('Response Time') }}</th>
                        <th class="px-6 py-4">{{ __('Causer') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Date / Time') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/50 transition cursor-pointer" onclick="window.location='{{ route('admin.system.api-logs.show', $log) }}'">
                            <td class="px-6 py-4">
                                @php
                                    $methodColor = match($log->method) {
                                        'GET' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        'POST' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'PUT', 'PATCH' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'DELETE' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                        default => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-bold ring-1 ring-inset {{ $methodColor }}">
                                    {{ $log->method }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-gray-700 max-w-xs truncate">{{ $log->url }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColor = match(true) {
                                        $log->status_code >= 200 && $log->status_code < 300 => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        $log->status_code >= 400 && $log->status_code < 500 => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        default => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-lg px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $statusColor }}">
                                    {{ $log->status_code }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-mono">
                                <span class="{{ $log->response_time > 500 ? 'text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded' : 'text-gray-600' }}">
                                    {{ $log->response_time }} ms
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-gray-800">{{ $log->causer->name ?? __('Player') }}</td>
                            <td class="px-6 py-4 text-right text-xs text-gray-400">{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <i class="ik ik-code text-3xl mb-2 block"></i>
                                {{ __('No API logs found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $logs->links() }}
            </div>
        @endif
    </x-card>
@endsection