<?php

namespace App\Operations\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DatabaseService
{
    public function overview(): array
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        $health = $this->checkHealth();

        return [
            'connection' => $connection,
            'driver' => $config['driver'] ?? null,
            'database' => $config['database'] ?? null,
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,

            'status' => $health['status'],
            'latency_ms' => $health['latency_ms'],

            'server_version' => $health['server_version'],
            'table_count' => $this->tableCount(),
            'database_size' => $this->databaseSize(),
        ];
    }

    public function tables(): array
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();

        try {
            $tables = DB::select(
                "
                SELECT
                    TABLE_NAME AS name,
                    ENGINE AS engine,
                    TABLE_ROWS AS rows_count,
                    DATA_LENGTH AS data_length,
                    INDEX_LENGTH AS index_length,
                    (DATA_LENGTH + INDEX_LENGTH) AS total_size
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                ORDER BY total_size DESC
                ",
                [$database]
            );

            return collect($tables)
                ->map(function ($table) {
                    return [
                        'name' => $table->name,
                        'engine' => $table->engine,
                        'rows' => (int) $table->rows_count,
                        'data_size' => (int) $table->data_length,
                        'index_size' => (int) $table->index_length,
                        'total_size' => (int) $table->total_size,
                        'total_size_human' => $this->formatBytes(
                            (int) $table->total_size
                        ),
                    ];
                })
                ->values()
                ->all();

        } catch (Throwable) {
            return [];
        }
    }

    public function migrations(): array
    {
        try {
            if (! Schema::hasTable('migrations')) {
                return [];
            }

            return DB::table('migrations')
                ->orderByDesc('batch')
                ->orderByDesc('id')
                ->limit(20)
                ->get([
                    'id',
                    'migration',
                    'batch',
                ])
                ->map(fn ($migration) => [
                    'id' => $migration->id,
                    'migration' => $migration->migration,
                    'batch' => $migration->batch,
                ])
                ->values()
                ->all();

        } catch (Throwable) {
            return [];
        }
    }

    protected function checkHealth(): array
    {
        $started = microtime(true);

        try {
            DB::select('SELECT 1');

            $serverVersion = DB::selectOne(
                'SELECT VERSION() AS version'
            );

            return [
                'status' => 'healthy',
                'latency_ms' => round(
                    (microtime(true) - $started) * 1000,
                    2
                ),
                'server_version' => $serverVersion->version ?? null,
            ];

        } catch (Throwable) {
            return [
                'status' => 'unhealthy',
                'latency_ms' => round(
                    (microtime(true) - $started) * 1000,
                    2
                ),
                'server_version' => null,
            ];
        }
    }

    protected function tableCount(): int
    {
        try {
            $database = DB::connection()->getDatabaseName();

            return (int) DB::selectOne(
                "
                SELECT COUNT(*) AS count
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                ",
                [$database]
            )->count;

        } catch (Throwable) {
            return 0;
        }
    }

    protected function databaseSize(): array
    {
        try {
            $database = DB::connection()->getDatabaseName();

            $result = DB::selectOne(
                "
                SELECT
                    COALESCE(SUM(DATA_LENGTH), 0) AS data_size,
                    COALESCE(SUM(INDEX_LENGTH), 0) AS index_size
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ?
                ",
                [$database]
            );

            $total = (int) $result->data_size +
                (int) $result->index_size;

            return [
                'bytes' => $total,
                'human' => $this->formatBytes($total),
                'data_bytes' => (int) $result->data_size,
                'index_bytes' => (int) $result->index_size,
            ];

        } catch (Throwable) {
            return [
                'bytes' => 0,
                'human' => '0 B',
                'data_bytes' => 0,
                'index_bytes' => 0,
            ];
        }
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = [
            'B',
            'KB',
            'MB',
            'GB',
            'TB',
        ];

        $power = floor(
            log($bytes, 1024)
        );

        $power = min(
            $power,
            count($units) - 1
        );

        return round(
            $bytes / (1024 ** $power),
            2
        ) . ' ' . $units[$power];
    }
}
