@extends('admin.layouts.main')

@section('title', __('Activity Logs'))

@section('content')
    <x-page-header title="{{ __('Activity Logs') }}" subtitle="{{ __('Track administrative actions and system events') }}" icon="ik ik-activity"
                    :breadcrumbs="['Home' => route('admin.dashboard'), 'System' => null, 'Activity Logs' => null]" />

    <x-card no-padding>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/50 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                        <th class="px-6 py-4">{{ __('ID') }}</th>
                        <th class="px-6 py-4">{{ __('Causer') }}</th>
                        <th class="px-6 py-4">{{ __('Action') }}</th>
                        <th class="px-6 py-4">{{ __('Description') }}</th>
                        <th class="px-6 py-4">{{ __('IP Address') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Date / Time') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-6 py-4 text-xs font-semibold text-gray-500">#{{ $log->id }}</td>
                            <td class="px-6 py-4 font-medium text-gray-800">
                                {{ $log->causer->name ?? __('System / Guest') }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $badgeColor = match($log->action) {
                                        'created' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'updated' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
                                        'deleted' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                        default => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $badgeColor }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 text-xs">{{ $log->description }}</td>
                            <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ $log->ip_address ?? '-' }}</td>
                            <td class="px-6 py-4 text-right text-xs text-gray-400">{{ $log->created_at->format('M d, Y H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                <i class="ik ik-inbox text-3xl mb-2 block"></i>
                                {{ __('No activity logs found.') }}
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