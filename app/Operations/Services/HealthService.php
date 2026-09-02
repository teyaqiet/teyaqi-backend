<?php

namespace App\Operations\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthService
{
    public function check(): array
    {
        return [
            'status' => $this->overallStatus(),

            'application' => [
                'status' => 'healthy',
                'name' => config('operations.application.name'),
                'environment' => config('operations.environment'),
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
            ],

            'database' => $this->database(),

            'cache' => $this->cache(),

            'storage' => $this->storage(),

            'queue' => $this->queue(),
        ];
    }

    protected function overallStatus(): string
    {
        $checks = [
            $this->database()['status'],
            $this->cache()['status'],
            $this->storage()['status'],
            $this->queue()['status'],
        ];

        return in_array('unhealthy', $checks, true)
            ? 'unhealthy'
            : 'healthy';
    }

    protected function database(): array
    {
        $started = microtime(true);

        try {
            DB::connection()->getPdo();

            DB::select('SELECT 1');

            return [
                'status' => 'healthy',
                'latency_ms' => round(
                    (microtime(true) - $started) * 1000,
                    2
                ),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'latency_ms' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function cache(): array
    {
        try {
            $key = 'operations_health_check';

            Cache::put($key, true, 10);

            return [
                'status' => Cache::get($key) === true
                    ? 'healthy'
                    : 'unhealthy',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function storage(): array
    {
        try {
            $disk = Storage::disk('local');

            $testFile = 'operations-health-check.txt';

            $disk->put($testFile, 'ok');

            $exists = $disk->exists($testFile);

            $disk->delete($testFile);

            return [
                'status' => $exists
                    ? 'healthy'
                    : 'unhealthy',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function queue(): array
    {
        try {
            $connection = config(
                'queue.default'
            );

            return [
                'status' => 'healthy',
                'connection' => $connection,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
}