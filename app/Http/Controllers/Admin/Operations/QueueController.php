<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\OperationAuditService;
use App\Operations\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function __construct(
        protected QueueService $queueService,
        protected OperationAuditService $auditService
    ) {}

    public function overview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->queueService->overview(),
        ]);
    }

    public function failed(Request $request): JsonResponse
    {
        $perPage = min(
            max((int) $request->input('per_page', 25), 1),
            100
        );

        $jobs = $this->queueService->failedJobs(
            perPage: $perPage,
            search: $request->input('search')
        );

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    public function showFailed(string $id): JsonResponse
    {
        $job = $this->queueService->findFailedJob($id);

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
    }

    public function retry(string $id): JsonResponse
    {
        $job = $this->queueService->findFailedJob($id);

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Failed job not found.',
            ], 404);
        }

        $success = $this->queueService->retryFailedJob($id);

        if (! $success) {
            $this->auditService->failed(
                action: 'queue.job.retry',
                module: 'queue',
                description: 'Failed to retry queue job.',
                metadata: [
                    'job_id' => $id,
                ],
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry queue job.',
            ], 500);
        }

        $this->auditService->success(
            action: 'queue.job.retry',
            module: 'queue',
            description: 'Queue job retried successfully.',
            metadata: [
                'job_id' => $id,
                'queue' => $job->queue,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Queue job retried successfully.',
        ]);
    }

    public function delete(string $id): JsonResponse
    {
        $job = $this->queueService->findFailedJob($id);

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Failed job not found.',
            ], 404);
        }

        $success = $this->queueService->deleteFailedJob($id);

        if (! $success) {
            $this->auditService->failed(
                action: 'queue.job.delete',
                module: 'queue',
                description: 'Failed to delete queue job.',
                metadata: [
                    'job_id' => $id,
                ],
            );

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete queue job.',
            ], 500);
        }

        $this->auditService->success(
            action: 'queue.job.delete',
            module: 'queue',
            description: 'Failed queue job deleted.',
            metadata: [
                'job_id' => $id,
                'queue' => $job->queue,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Failed queue job deleted successfully.',
        ]);
    }
}