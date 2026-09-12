<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class QueueController extends Controller
{
    public function __construct(
        protected QueueService $queueService
    ) {
    }

    /**
     * Queue overview.
     */
    public function overview(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->queueService->overview(),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load queue information.',
            ], 500);
        }
    }

    /**
     * List pending jobs.
     *
     * Kept as a dedicated endpoint for compatibility with
     * the current Queue Monitor frontend.
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $perPage = min(
                max(
                    (int) $request->input('per_page', 25),
                    1
                ),
                100
            );

            $search = trim(
                (string) $request->input('search', '')
            ) ?: null;

            $jobs = $this->queueService->jobs(
                $perPage,
                $search,
                'pending'
            );

            return response()->json([
                'success' => true,
                'data' => $jobs,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load pending jobs.',
            ], 500);
        }
    }

    /**
     * List queued jobs.
     *
     * Supported filters:
     * - all
     * - pending
     * - processing
     * - delayed
     * - stuck
     */
    public function jobs(Request $request): JsonResponse
    {
        try {
            $perPage = min(
                max(
                    (int) $request->input('per_page', 25),
                    1
                ),
                100
            );

            $filter = strtolower(
                trim(
                    (string) $request->input('filter', 'all')
                )
            );

            if (! in_array(
                $filter,
                [
                    'all',
                    'pending',
                    'processing',
                    'delayed',
                    'stuck',
                ],
                true
            )) {
                $filter = 'all';
            }

            $search = trim(
                (string) $request->input('search', '')
            ) ?: null;

            $jobs = $this->queueService->jobs(
                $perPage,
                $search,
                $filter
            );

            return response()->json([
                'success' => true,
                'data' => $jobs,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load queued jobs.',
            ], 500);
        }
    }

    /**
     * Show queued job.
     */
    public function showJob(int $id): JsonResponse
    {
        try {
            $job = $this->queueService->findJob($id);

            if (! $job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Queued job not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $job,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load queued job.',
            ], 500);
        }
    }

    /**
     * List failed jobs.
     */
    public function failed(Request $request): JsonResponse
    {
        try {
            $perPage = min(
                max(
                    (int) $request->input('per_page', 25),
                    1
                ),
                100
            );

            $search = trim(
                (string) $request->input('search', '')
            ) ?: null;

            $jobs = $this->queueService->failedJobs(
                $perPage,
                $search
            );

            return response()->json([
                'success' => true,
                'data' => $jobs,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load failed jobs.',
            ], 500);
        }
    }

    /**
     * Show failed job.
     */
    public function showFailed(int $id): JsonResponse
    {
        try {
            $job = $this->queueService->findFailedJob(
                (string) $id
            );

            if (! $job) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed job not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $job,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load failed job.',
            ], 500);
        }
    }

    /**
     * Retry failed job.
     */
    public function retry(int $id): JsonResponse
    {
        try {
            $success = $this->queueService->retryFailedJob(
                (string) $id
            );

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to retry failed job.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Job has been returned to the queue.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retry failed job.',
            ], 500);
        }
    }

    /**
     * Delete failed job.
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $success = $this->queueService->deleteFailedJob(
                (string) $id
            );

            if (! $success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed job not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Failed job deleted.',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete failed job.',
            ], 500);
        }
    }

    public function showPending(int $id): JsonResponse
{
    try {
        $job = $this->queueService->findJob($id);

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Queued job not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    } catch (Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'message' => 'Unable to load queued job.',
        ], 500);
    }
}


}