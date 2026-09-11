<?php

namespace App\Operations\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ApplicationHealthService
{
    public function check(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');

            return [
                'healthy' => true,
                'message' => 'Database connection is healthy.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'healthy' => false,
                'message' => 'Database connection failed.',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkCache(): array
    {
        $key = 'operations:health:cache';

        try {
            $value = uniqid('health_', true);

            Cache::put($key, $value, 60);

            $retrieved = Cache::get($key);

            Cache::forget($key);

            if ($retrieved !== $value) {
                return [
                    'healthy' => false,
                    'message' => 'Cache read/write verification failed.',
                ];
            }

            return [
                'healthy' => true,
                'message' => 'Cache read/write is healthy.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'healthy' => false,
                'message' => 'Cache check failed.',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function checkStorage(): array
    {
        $disk = 'local';

        $path = 'operations/health-check.txt';

        try {
            $contents = 'health-check-' . uniqid('', true);

            Storage::disk($disk)->put($path, $contents);

            $retrieved = Storage::disk($disk)->get($path);

            Storage::disk($disk)->delete($path);

            if ($retrieved !== $contents) {
                return [
                    'healthy' => false,
                    'message' => 'Storage read/write verification failed.',
                ];
            }

            return [
                'healthy' => true,
                'message' => 'Storage read/write is healthy.',
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'healthy' => false,
                'message' => 'Storage check failed.',
                'error' => $e->getMessage(),
            ];
        }
    }
}
