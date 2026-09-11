<?php

namespace App\Operations\Providers;

interface ProcessProviderInterface
{
    /**
     * Get a list of running processes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(): array;

    /**
     * Get a single process by PID.
     */
    public function find(int $pid): ?array;

    /**
     * Get provider/system information.
     */
    public function info(): array;
}