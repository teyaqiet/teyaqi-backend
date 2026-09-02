<?php

namespace App\Operations\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CacheService
{
    public function overview(): array
    {
        $driver = config('cache.default');

        $health = $this->checkHealth();

        return [
            'driver' => $driver,
            'status' => $health['status'],
            'latency_ms' => $health['latency_ms'],
            'prefix' => config('cache.prefix'),
            'default_store' => config('cache.default'),
        ];
    }

    public function clearApplicationCache(): array
    {
        return $this->runCommand('cache:clear');
    }

    public function clearConfigCache(): array
    {
        return $this->runCommand('config:clear');
    }

    public function clearRouteCache(): array
    {
        return $this->runCommand('route:clear');
    }

    public function clearViewCache(): array
    {
        return $this->runCommand('view:clear');
    }

    public function clearAllCaches(): array
    {
        $commands = [
            'cache:clear',
            'config:clear',
            'route:clear',
            'view:clear',
        ];

        $results = [];

        foreach ($commands as $command) {
            $results[$command] = $this->runCommand($command);

            if (! $results[$command]['success']) {
                return [
                    'success' => false,
                    'results' => $results,
                ];
            }
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    protected function checkHealth(): array
    {
        $key = 'operations_cache_health_' . uniqid();

        $started = microtime(true);

        try {
            Cache::put($key, 'ok', 10);

            $value = Cache::get($key);

            Cache::forget($key);

            if ($value !== 'ok') {
                return [
                    'status' => 'unhealthy',
                    'latency_ms' => round(
                        (microtime(true) - $started) * 1000,
                        2
                    ),
                ];
            }

            return [
                'status' => 'healthy',
                'latency_ms' => round(
                    (microtime(true) - $started) * 1000,
                    2
                ),
            ];
        } catch (Throwable) {
            return [
                'status' => 'unhealthy',
                'latency_ms' => round(
                    (microtime(true) - $started) * 1000,
                    2
                ),
            ];
        }
    }

    protected function runCommand(string $command): array
    {
        try {
            $exitCode = Artisan::call($command);

            return [
                'success' => $exitCode === 0,
                'command' => $command,
                'output' => trim(
                    Artisan::output()
                ),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'command' => $command,
                'output' => $e->getMessage(),
            ];
        }
    }
}