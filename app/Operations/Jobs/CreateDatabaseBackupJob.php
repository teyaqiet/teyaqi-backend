<?php

namespace App\Operations\Jobs;

use App\Models\OperationBackup;
use App\Operations\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CreateDatabaseBackupJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Only one attempt.
     *
     * A database dump should not automatically be repeated
     * blindly because a partial/failed dump may have been created.
     */
    public int $tries = 1;

    /**
     * Maximum execution time.
     */
    public int $timeout;

    public function __construct(
        public int $backupId
    ) {
        $this->timeout = (int) config(
            'operations.backups.mysql.timeout',
            300
        );
    }

    public function handle(
        BackupService $backupService
    ): void {
        $backup = OperationBackup::find($this->backupId);

        if (! $backup) {
            return;
        }

        /*
         * If the backup was deleted while waiting in the queue,
         * there is nothing left to process.
         */
        if ($backup->status !== 'pending') {
            return;
        }

        $backupService->executeDatabaseBackup($backup);
    }

    public function failed(Throwable $exception): void
    {
        $backup = OperationBackup::find($this->backupId);

        if (! $backup) {
            return;
        }

        $backup->update([
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ]);
    }
}