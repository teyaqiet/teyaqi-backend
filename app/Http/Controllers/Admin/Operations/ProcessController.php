<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\OperationAuditService;
use App\Operations\Services\ProcessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ProcessController extends Controller
{
    public function __construct(
        protected ProcessService $processService,
        protected OperationAuditService $auditService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $limit = min(
                max(
                    (int) $request->input('limit', 100),
                    1
                ),
                500
            );

            $processes = $this->processService->processes(
                search: $request->input('search'),
                sort: $request->input('sort', 'cpu'),
                direction: $request->input('direction', 'desc'),
                limit: $limit,
            );

            return response()->json([
                'success' => true,

                'data' => [
                    'platform' => PHP_OS_FAMILY,

                    'provider' => $this->processService
                        ->provider()
                        ->info(),

                    'total' => count($processes),

                    'processes' => $processes,
                ],
            ]);

        } catch (Throwable $e) {

            $this->auditService->log(
                action: 'processes.index',
                module: 'server',
                status: 'failed',
                description: 'Failed to retrieve system processes.',
                metadata: [
                    'error' => $e->getMessage(),
                ],
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve processes.',
            ], 500);
        }
    }

    public function overview(): JsonResponse
    {
        try {

            return response()->json([
                'success' => true,
                'data' => $this->processService->overview(),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve process overview.',
            ], 500);
        }
    }

    public function show(int $pid): JsonResponse
    {
        try {

            $process = $this->processService->find($pid);

            if (!$process) {

                return response()->json([
                    'success' => false,
                    'message' => 'Process not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $process,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve process.',
            ], 500);
        }
    }
}
