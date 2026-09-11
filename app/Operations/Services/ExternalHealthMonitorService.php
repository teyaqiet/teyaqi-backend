<?php

namespace App\Operations\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class ExternalHealthMonitorService
{
    public function check(): array
    {
        $url = config(
            'operations.health.url',
            url('/health')
        );

        $timeout = (int) config(
            'operations.health.timeout',
            10
        );

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($url);

            $handlerStats = $response->handlerStats();

            $totalTimeUs = $handlerStats['total_time_us'] ?? null;

            return [
                'reachable' => true,
                'healthy' => $response->successful(),
                'status_code' => $response->status(),
                'url' => $url,
                'response_time_ms' => $totalTimeUs !== null
                    ? round($totalTimeUs / 1000, 2)
                    : null,
                'body' => $response->json(),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'reachable' => false,
                'healthy' => false,
                'status_code' => null,
                'url' => $url,
                'response_time_ms' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}