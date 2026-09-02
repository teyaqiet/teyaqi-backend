<?php

namespace App\Jobs;

use App\Services\BroadcastRecoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecoverStaleBroadcastRecipients implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum attempts.
     */
    public int $tries = 3;

    /**
     * Maximum execution time.
     */
    public int $timeout = 120;

    /**
     * Create the job.
     */
    public function __construct()
    {
        $this->onQueue('broadcasts');
    }

    /**
     * Execute the recovery job.
     */
    public function handle(
        BroadcastRecoveryService $recoveryService
    ): void {
        $result = $recoveryService->recover();

        Log::info(
            'Broadcast recovery completed.',
            $result
        );
    }

    /**
     * Handle a permanently failed recovery job.
     */
    public function failed(
        Throwable $exception
    ): void {
        Log::error(
            'Broadcast recovery job failed.',
            [
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
            ]
        );
    }
}