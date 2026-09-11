<?php

namespace App\Operations\Providers;

use Symfony\Component\Process\Process;
use Throwable;

class LinuxProcessProvider implements ProcessProviderInterface
{
    public function list(): array
    {
        try {
            $process = new Process([
                'ps',
                '-eo',
                'pid=,user=,stat=,%cpu=,%mem=,etime=,comm=,args=',
                '--sort=-%cpu',
            ]);

            $process->setTimeout(15);
            $process->run();

            if (!$process->isSuccessful()) {
                return [];
            }

            $lines = preg_split(
                '/\r\n|\r|\n/',
                trim($process->getOutput())
            );

            $result = [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $parts = preg_split('/\s+/', $line, 8);

                if (count($parts) < 7) {
                    continue;
                }

                $result[] = [
                    'pid' => (int) $parts[0],
                    'user' => $parts[1] ?? null,
                    'status' => $parts[2] ?? null,
                    'cpu_percent' => isset($parts[3])
                        ? (float) $parts[3]
                        : 0,
                    'memory_percent' => isset($parts[4])
                        ? (float) $parts[4]
                        : 0,
                    'runtime' => $parts[5] ?? null,
                    'name' => $parts[6] ?? 'Unknown',
                    'command' => $parts[7] ?? null,
                    'cpu_time' => null,
                    'memory_bytes' => null,
                    'started_at' => null,
                ];
            }

            return $result;
        } catch (Throwable) {
            return [];
        }
    }

    public function find(int $pid): ?array
    {
        try {
            $process = new Process([
                'ps',
                '-p',
                (string) $pid,
                '-o',
                'pid=,user=,stat=,%cpu=,%mem=,etime=,comm=,args=',
            ]);

            $process->setTimeout(10);
            $process->run();

            if (!$process->isSuccessful()) {
                return null;
            }

            $line = trim($process->getOutput());

            if ($line === '') {
                return null;
            }

            $parts = preg_split('/\s+/', $line, 8);

            if (count($parts) < 7) {
                return null;
            }

            return [
                'pid' => (int) $parts[0],
                'user' => $parts[1] ?? null,
                'status' => $parts[2] ?? null,
                'cpu_percent' => isset($parts[3])
                    ? (float) $parts[3]
                    : 0,
                'memory_percent' => isset($parts[4])
                    ? (float) $parts[4]
                    : 0,
                'runtime' => $parts[5] ?? null,
                'name' => $parts[6] ?? 'Unknown',
                'command' => $parts[7] ?? null,
                'cpu_time' => null,
                'memory_bytes' => null,
                'started_at' => null,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    public function info(): array
    {
        return [
            'platform' => 'linux',
            'provider' => static::class,
        ];
    }
}