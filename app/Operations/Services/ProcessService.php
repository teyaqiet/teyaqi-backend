<?php

namespace App\Operations\Services;

use App\Operations\Providers\LinuxProcessProvider;
use App\Operations\Providers\ProcessProviderInterface;
use App\Operations\Providers\WindowsProcessProvider;
use RuntimeException;

class ProcessService
{
    protected ProcessProviderInterface $provider;

    public function __construct()
    {
        $this->provider = $this->resolveProvider();
    }

    protected function resolveProvider(): ProcessProviderInterface
    {
        return match (PHP_OS_FAMILY) {
            'Windows' => new WindowsProcessProvider(),
            'Linux' => new LinuxProcessProvider(),

            default => throw new RuntimeException(
                'Unsupported operating system: ' . PHP_OS_FAMILY
            ),
        };
    }

    public function provider(): ProcessProviderInterface
    {
        return $this->provider;
    }

    public function overview(): array
    {
        $processes = $this->provider->list();

        $collection = collect($processes);

        $topCpu = $collection
            ->filter(fn ($process) =>
                isset($process['cpu_percent'])
            )
            ->sortByDesc(fn ($process) =>
                (float) ($process['cpu_percent'] ?? 0)
            )
            ->take(10)
            ->values()
            ->all();

        $topMemory = $collection
            ->filter(fn ($process) =>
                isset($process['memory_bytes'])
            )
            ->sortByDesc(fn ($process) =>
                (int) ($process['memory_bytes'] ?? 0)
            )
            ->take(10)
            ->values()
            ->all();

        return [
            'platform' => PHP_OS_FAMILY,

            'provider' => $this->provider->info(),

            'total' => $collection->count(),

            'top_cpu' => $topCpu,

            'top_memory' => $topMemory,

            'processes' => $processes,
        ];
    }

    public function processes(
        ?string $search = null,
        string $sort = 'cpu',
        string $direction = 'desc',
        int $limit = 100
    ): array {
        $processes = collect(
            $this->provider->list()
        );

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($search !== null && trim($search) !== '') {

            $search = strtolower(
                trim($search)
            );

            $processes = $processes->filter(
                function ($process) use ($search) {

                    $name = strtolower(
                        (string) ($process['name'] ?? '')
                    );

                    $pid = strtolower(
                        (string) ($process['pid'] ?? '')
                    );

                    $user = strtolower(
                        (string) ($process['user'] ?? '')
                    );

                    $command = strtolower(
                        (string) ($process['command'] ?? '')
                    );

                    return str_contains($name, $search)
                        || str_contains($pid, $search)
                        || str_contains($user, $search)
                        || str_contains($command, $search);
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'pid',
            'name',
            'cpu',
            'memory',
        ];

        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'cpu';
        }

        switch ($sort) {

            case 'pid':

                $processes = $processes->sortBy(
                    fn ($process) =>
                        (int) ($process['pid'] ?? 0)
                );

                break;

            case 'name':

                $processes = $processes->sortBy(
                    fn ($process) =>
                        strtolower(
                            (string) ($process['name'] ?? '')
                        )
                );

                break;

            case 'memory':

                $processes = $processes->sortBy(
                    fn ($process) =>
                        (int) ($process['memory_bytes'] ?? 0)
                );

                break;

            case 'cpu':
            default:

                $processes = $processes->sortBy(
                    fn ($process) =>
                        (float) ($process['cpu_percent'] ?? -1)
                );

                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Direction
        |--------------------------------------------------------------------------
        */

        if (strtolower($direction) !== 'asc') {
            $processes = $processes->reverse();
        }

        /*
        |--------------------------------------------------------------------------
        | Limit
        |--------------------------------------------------------------------------
        */

        $limit = min(
            max($limit, 1),
            500
        );

        return $processes
            ->take($limit)
            ->values()
            ->all();
    }

    public function find(int $pid): ?array
    {
        return $this->provider->find($pid);
    }
}
