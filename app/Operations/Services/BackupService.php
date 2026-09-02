<?php

namespace App\Operations\Services;

use App\Models\OperationBackup;
use App\Operations\Jobs\CreateDatabaseBackupJob;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackupService
{
    /**
     * Get backup system overview.
     */
    public function overview(): array
    {
        $disk = config(
            'operations.backups.disk',
            'local'
        );

        $path = config(
            'operations.backups.path',
            'operations/backups'
        );

        $backups = OperationBackup::query()
            ->where('status', 'completed')
            ->latest()
            ->get();

        $totalSize = $backups->sum(
            fn ($backup) => (int) $backup->size
        );

        return [
            'enabled' => (bool) config(
                'operations.backups.enabled',
                true
            ),

            'disk' => $disk,

            'path' => $path,

            'total_backups' => $backups->count(),

            'total_size' => $totalSize,

            'total_size_human' => $this->formatBytes(
                $totalSize
            ),

            'latest_backup' => $backups->first(),
        ];
    }

    /**
     * Get paginated backup history.
     */
    public function list(int $perPage = 25)
    {
        return OperationBackup::query()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a backup by ID.
     */
    public function find(int $id): ?OperationBackup
    {
        return OperationBackup::find($id);
    }

    /**
     * Create a backup record and queue the actual backup job.
     *
     * IMPORTANT:
     * This method does NOT execute mysqldump.
     *
     * The HTTP request only creates the pending record and
     * dispatches the queue job.
     */
    public function createDatabaseBackup(
        ?int $userId = null
    ): OperationBackup {
        if (! config('operations.backups.enabled', true)) {
            throw new \RuntimeException(
                'Database backups are currently disabled.'
            );
        }

        $diskName = config(
            'operations.backups.disk',
            'local'
        );

        $directory = trim(
            config(
                'operations.backups.path',
                'operations/backups'
            ),
            '/'
        );

        $filename = 'database_' .
            now()->format('Y_m_d_H_i_s') .
            '.sql';

        $path = $directory . '/' . $filename;

        /*
         * Create the backup record first.
         */
        $backup = OperationBackup::create([
            'type' => 'database',
            'disk' => $diskName,
            'path' => $path,
            'filename' => $filename,
            'status' => 'pending',
            'created_by' => $userId,
        ]);

        /*
         * Queue the actual database dump.
         *
         * The queue worker will execute this from the CLI
         * context instead of the HTTP/web process.
         */
        CreateDatabaseBackupJob::dispatch(
            $backup->id
        );

        return $backup->fresh();
    }

    /**
     * Execute an actual database backup.
     *
     * This method is called by CreateDatabaseBackupJob.
     *
     * DO NOT call this method directly from an HTTP controller.
     */
    public function executeDatabaseBackup(
        OperationBackup $backup
    ): OperationBackup {
        /*
         * ---------------------------------------------------------
         * Mark backup as running.
         * ---------------------------------------------------------
         */
        $backup->update([
            'status' => 'running',
            'error' => null,
        ]);

        $diskName = $backup->disk;
        $path = $backup->path;

        $storage = null;
        $optionFile = null;

        try {

            /*
             * ---------------------------------------------------------
             * Database configuration
             * ---------------------------------------------------------
             */

            $connection = config(
                'database.default'
            );

            $databaseConfig = config(
                "database.connections.{$connection}"
            );

            if (! $databaseConfig) {
                throw new \RuntimeException(
                    "Database connection [{$connection}] is not configured."
                );
            }

            $driver = $databaseConfig['driver'] ?? null;

            if (! in_array(
                $driver,
                ['mysql', 'mariadb'],
                true
            )) {
                throw new \RuntimeException(
                    "Database backups currently support MySQL/MariaDB only. Current driver: {$driver}"
                );
            }

            $database = $databaseConfig['database'] ?? null;

            $host = $databaseConfig['host']
                ?? '127.0.0.1';

            $port = (int) (
                $databaseConfig['port']
                ?? 3306
            );

            $username = $databaseConfig['username']
                ?? null;

            $password = $databaseConfig['password']
                ?? '';

            if (! $database) {
                throw new \RuntimeException(
                    'Database name is not configured.'
                );
            }

            if (! $username) {
                throw new \RuntimeException(
                    'Database username is not configured.'
                );
            }

            /*
             * ---------------------------------------------------------
             * mysqldump configuration
             * ---------------------------------------------------------
             */

            $binary = config(
                'operations.backups.mysql.binary',
                'mysqldump'
            );

            $timeout = (int) config(
                'operations.backups.mysql.timeout',
                300
            );

            /*
             * ---------------------------------------------------------
             * Verify mysqldump exists when an absolute path is used.
             * ---------------------------------------------------------
             */

            if (
                str_contains($binary, '\\') ||
                str_contains($binary, '/')
            ) {
                if (! file_exists($binary)) {
                    throw new \RuntimeException(
                        "mysqldump binary was not found: {$binary}"
                    );
                }
            }

            /*
             * ---------------------------------------------------------
             * Backup storage
             * ---------------------------------------------------------
             */

            $storage = Storage::disk($diskName);

            $directory = dirname($path);

            if (
                $directory === '.' ||
                $directory === DIRECTORY_SEPARATOR
            ) {
                $directory = '';
            }

            if ($directory !== '') {
                $storage->makeDirectory(
                    $directory
                );
            }

            $fullPath = $storage->path(
                $path
            );

            $parentDirectory = dirname(
                $fullPath
            );

            if (! is_dir($parentDirectory)) {
                if (
                    ! mkdir(
                        $parentDirectory,
                        0755,
                        true
                    ) &&
                    ! is_dir($parentDirectory)
                ) {
                    throw new \RuntimeException(
                        'Unable to create backup directory.'
                    );
                }
            }

            /*
             * ---------------------------------------------------------
             * Temporary credentials directory
             * ---------------------------------------------------------
             */

            $tempDirectory = storage_path(
                'app/temp'
            );

            if (! is_dir($tempDirectory)) {
                if (
                    ! mkdir(
                        $tempDirectory,
                        0700,
                        true
                    ) &&
                    ! is_dir($tempDirectory)
                ) {
                    throw new \RuntimeException(
                        'Unable to create temporary directory.'
                    );
                }
            }

            /*
             * ---------------------------------------------------------
             * Temporary MariaDB credentials file
             * ---------------------------------------------------------
             *
             * The password is NOT placed directly in the command
             * arguments.
             */

            $optionFile = $tempDirectory .
                DIRECTORY_SEPARATOR .
                'mysqldump_' .
                bin2hex(random_bytes(16)) .
                '.cnf';

            $optionFileContents =
                "[client]\r\n" .
                "host={$host}\r\n" .
                "port={$port}\r\n" .
                "user={$username}\r\n" .
                "password={$password}\r\n";

            if (
                file_put_contents(
                    $optionFile,
                    $optionFileContents,
                    LOCK_EX
                ) === false
            ) {
                throw new \RuntimeException(
                    'Unable to create temporary database credentials file.'
                );
            }

            /*
             * Restrict permissions where supported.
             */
            @chmod(
                $optionFile,
                0600
            );

            /*
             * ---------------------------------------------------------
             * Run mysqldump
             * ---------------------------------------------------------
             */

            $command = [
                $binary,

                '--defaults-extra-file=' .
                    $optionFile,

                /*
                 * Explicit TCP connection.
                 */
                '--protocol=tcp',

                '--host=' . $host,

                '--port=' . $port,

                /*
                 * Keep the dump consistent without locking
                 * InnoDB tables for the whole operation.
                 */
                '--single-transaction',

                /*
                 * Stream rows instead of loading everything
                 * into memory.
                 */
                '--quick',

                /*
                 * Include stored procedures/functions.
                 */
                '--routines',

                /*
                 * Include triggers.
                 */
                '--triggers',

                /*
                 * Write directly to the destination file.
                 */
                '--result-file=' . $fullPath,

                /*
                 * Database name.
                 */
                $database,
            ];

            $process = Process::timeout(
                $timeout
            )->run($command);

            /*
             * ---------------------------------------------------------
             * Process failure
             * ---------------------------------------------------------
             */

            if ($process->failed()) {

                $error = trim(
                    $process->errorOutput()
                );

                /*
                 * Never expose the database password.
                 */
                throw new \RuntimeException(
                    $error
                        ?: 'Database dump process failed.'
                );
            }

            /*
             * ---------------------------------------------------------
             * Validate backup file
             * ---------------------------------------------------------
             */

            if (! file_exists($fullPath)) {
                throw new \RuntimeException(
                    'Backup process completed, but the backup file was not created.'
                );
            }

            $size = filesize(
                $fullPath
            );

            if (
                $size === false ||
                $size <= 0
            ) {
                throw new \RuntimeException(
                    'Backup file was created but is empty.'
                );
            }

            /*
             * ---------------------------------------------------------
             * Generate SHA-256 checksum
             * ---------------------------------------------------------
             */

            $checksum = hash_file(
                'sha256',
                $fullPath
            );

            if ($checksum === false) {
                throw new \RuntimeException(
                    'Unable to generate backup checksum.'
                );
            }

            /*
             * ---------------------------------------------------------
             * Mark backup completed
             * ---------------------------------------------------------
             */

            $backup->update([
                'status' => 'completed',

                'size' => $size,

                'checksum' => $checksum,

                'completed_at' => now(),

                'error' => null,
            ]);

            return $backup->fresh();

        } catch (Throwable $e) {

            /*
             * ---------------------------------------------------------
             * Remove partial backup
             * ---------------------------------------------------------
             */

            try {

                if (
                    $storage &&
                    $storage->exists($path)
                ) {
                    $storage->delete(
                        $path
                    );
                }

            } catch (Throwable) {
                /*
                 * Preserve the original exception.
                 */
            }

            /*
             * ---------------------------------------------------------
             * Mark backup failed
             * ---------------------------------------------------------
             */

            $backup->update([
                'status' => 'failed',

                'error' => $e->getMessage(),
            ]);

            throw $e;

        } finally {

            /*
             * ---------------------------------------------------------
             * Remove temporary credentials file
             * ---------------------------------------------------------
             */

            if (
                $optionFile &&
                file_exists($optionFile)
            ) {
                @unlink(
                    $optionFile
                );
            }
        }
    }

    /**
     * Delete a backup and its physical file.
     */
    public function delete(
        OperationBackup $backup
    ): bool {
        $disk = Storage::disk(
            $backup->disk
        );

        if (
            $disk->exists(
                $backup->path
            )
        ) {
            $disk->delete(
                $backup->path
            );
        }

        return $backup->delete();
    }

    /**
     * Format bytes into a human-readable value.
     */
    protected function formatBytes(
        int $bytes
    ): string {
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

        $power = min(
            floor(
                log($bytes, 1024)
            ),
            count($units) - 1
        );

        return round(
            $bytes / (1024 ** $power),
            2
        ) . ' ' . $units[$power];
    }
}
