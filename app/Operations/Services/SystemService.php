<?php

namespace App\Operations\Services;

use Illuminate\Support\Facades\Process;
use Throwable;

class SystemService
{
    public function information(): array
    {
        return [
            'server' => $this->server(),
            'php' => $this->php(),
            'laravel' => $this->laravel(),
            'resources' => $this->resources(),
        ];
    }

    protected function server(): array
    {
        return [
            'os' => PHP_OS_FAMILY,
            'os_version' => php_uname('r'),
            'hostname' => gethostname(),
            'architecture' => php_uname('m'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        ];
    }

    protected function php(): array
    {
        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];
    }

    protected function laravel(): array
    {
        return [
            'version' => app()->version(),
            'environment' => app()->environment(),
            'debug' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'maintenance' => app()->isDownForMaintenance(),
        ];
    }

    protected function resources(): array
    {
        return [
            'memory' => $this->memory(),
            'disk' => $this->disk(),
            'cpu' => $this->cpu(),
        ];
    }

    protected function memory(): array
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                return $this->windowsMemory();
            }

            $total = $this->linuxMemoryValue('MemTotal');
            $available = $this->linuxMemoryValue('MemAvailable');

            if ($total === null || $available === null) {
                return [
                    'status' => 'unavailable',
                ];
            }

            $used = $total - $available;

            return [
                'status' => 'available',
                'total_mb' => round($total / 1024, 2),
                'used_mb' => round($used / 1024, 2),
                'available_mb' => round($available / 1024, 2),
                'usage_percent' => round(($used / $total) * 100, 2),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unavailable',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function windowsMemory(): array
    {
        try {
            $result = Process::run(
                'powershell -NoProfile -Command "(Get-CimInstance Win32_OperatingSystem | Select-Object TotalVisibleMemorySize,FreePhysicalMemory | ConvertTo-Json -Compress)"'
            );

            if (! $result->successful()) {
                return [
                    'status' => 'unavailable',
                ];
            }

            $data = json_decode(
                trim($result->output()),
                true
            );

            if (! is_array($data)) {
                return [
                    'status' => 'unavailable',
                ];
            }

            $total = ((float) $data['TotalVisibleMemorySize']) / 1024;
            $available = ((float) $data['FreePhysicalMemory']) / 1024;
            $used = $total - $available;

            return [
                'status' => 'available',
                'total_mb' => round($total, 2),
                'used_mb' => round($used, 2),
                'available_mb' => round($available, 2),
                'usage_percent' => round(($used / $total) * 100, 2),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unavailable',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function linuxMemoryValue(string $key): ?float
    {
        if (! is_readable('/proc/meminfo')) {
            return null;
        }

        $contents = file_get_contents('/proc/meminfo');

        foreach (explode("\n", $contents) as $line) {
            if (str_starts_with($line, $key . ':')) {
                preg_match('/\d+/', $line, $matches);

                return isset($matches[0])
                    ? (float) $matches[0]
                    : null;
            }
        }

        return null;
    }

    protected function disk(): array
    {
        try {
            $path = base_path();

            $total = disk_total_space($path);
            $free = disk_free_space($path);

            if ($total === false || $free === false) {
                return [
                    'status' => 'unavailable',
                ];
            }

            $used = $total - $free;

            return [
                'status' => 'available',
                'total_gb' => round($total / 1024 ** 3, 2),
                'used_gb' => round($used / 1024 ** 3, 2),
                'free_gb' => round($free / 1024 ** 3, 2),
                'usage_percent' => round(($used / $total) * 100, 2),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unavailable',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function cpu(): array
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                return $this->windowsCpu();
            }

            if (is_readable('/proc/loadavg')) {
                $load = trim(file_get_contents('/proc/loadavg'));

                $parts = preg_split('/\s+/', $load);

                return [
                    'status' => 'available',
                    'load_1m' => isset($parts[0])
                        ? (float) $parts[0]
                        : null,
                    'load_5m' => isset($parts[1])
                        ? (float) $parts[1]
                        : null,
                    'load_15m' => isset($parts[2])
                        ? (float) $parts[2]
                        : null,
                    'cores' => $this->cpuCores(),
                ];
            }

            return [
                'status' => 'unavailable',
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unavailable',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function windowsCpu(): array
    {
        try {
            $result = Process::run(
                'powershell -NoProfile -Command "(Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average | Select-Object Average | ConvertTo-Json -Compress)"'
            );

            if (! $result->successful()) {
                return [
                    'status' => 'unavailable',
                ];
            }

            $data = json_decode(
                trim($result->output()),
                true
            );

            return [
                'status' => 'available',
                'usage_percent' => isset($data['Average'])
                    ? round((float) $data['Average'], 2)
                    : null,
                'cores' => $this->cpuCores(),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'unavailable',
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function cpuCores(): ?int
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $result = Process::run(
                    'powershell -NoProfile -Command "(Get-CimInstance Win32_Processor | Measure-Object -Property NumberOfLogicalProcessors -Sum).Sum"'
                );

                return $result->successful()
                    ? (int) trim($result->output())
                    : null;
            }

            if (is_readable('/proc/cpuinfo')) {
                $contents = file_get_contents('/proc/cpuinfo');

                return substr_count(
                    $contents,
                    'processor'
                );
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }
}