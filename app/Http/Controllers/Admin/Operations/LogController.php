<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\LogReader;
use App\Operations\Services\OperationAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LogController extends Controller
{
    public function __construct(
        protected LogReader $logReader,
        protected OperationAuditService $auditService
    ) {}

    /**
     * Log Center overview.
     */
    public function overview(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'file' => $this->logReader->information(),
                    'levels' => [
                        'emergency',
                        'alert',
                        'critical',
                        'error',
                        'warning',
                        'notice',
                        'info',
                        'debug',
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve log information.',
            ], 500);
        }
    }

    /**
     * List parsed log entries.
     */
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

            $entries = $this->logReader->entries(
                level: $request->input('level'),
                search: $request->input('search'),
                limit: $limit,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'entries' => $entries,
                    'total' => count($entries),
                    'file' => $this->logReader->information(),
                ],
            ]);
        } catch (Throwable $e) {
            $this->auditService->failed(
                action: 'logs.index',
                module: 'system',
                description: 'Failed to retrieve application logs.',
                metadata: [
                    'error' => $e->getMessage(),
                ],
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve application logs.',
            ], 500);
        }
    }

    /**
     * Show a single parsed log entry.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $entry = $this->logReader->find($id);

            if (! $entry) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log entry not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $entry,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve log entry.',
            ], 500);
        }
    }

    /**
     * Clear the Laravel application log.
     */
    public function clear(): JsonResponse
    {
        try {
            $information = $this->logReader->information();

            $this->logReader->clear();

            $this->auditService->success(
                action: 'logs.clear',
                module: 'system',
                description: 'Application log file was cleared.',
                metadata: [
                    'file' => $information['filename'] ?? 'laravel.log',
                    'previous_size' => $information['size'] ?? 0,
                ],
            );

            return response()->json([
                'success' => true,
                'message' => 'Application logs cleared successfully.',
                'data' => [
                    'file' => $this->logReader->information(),
                ],
            ]);
        } catch (Throwable $e) {
            $this->auditService->failed(
                action: 'logs.clear',
                module: 'system',
                description: 'Failed to clear application logs.',
                metadata: [
                    'error' => $e->getMessage(),
                ],
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to clear application logs.',
            ], 500);
        }
    }

    /**
     * Download the current Laravel log file.
     */
    public function download(): StreamedResponse|JsonResponse
    {
        try {
            $path = $this->logReader->path();

            $filename = 'laravel-' . now()->format(
                'Y-m-d-H-i-s'
            ) . '.log';

            $this->auditService->success(
                action: 'logs.download',
                module: 'system',
                description: 'Application log file downloaded.',
                metadata: [
                    'file' => basename($path),
                    'size' => filesize($path),
                ],
            );

            return response()->streamDownload(
                function () use ($path) {
                    $handle = fopen($path, 'rb');

                    if ($handle === false) {
                        return;
                    }

                    while (! feof($handle)) {
                        echo fread($handle, 8192);
                    }

                    fclose($handle);
                },
                $filename,
                [
                    'Content-Type' => 'text/plain; charset=UTF-8',
                ]
            );
        } catch (Throwable $e) {
            $this->auditService->failed(
                action: 'logs.download',
                module: 'system',
                description: 'Failed to download application log.',
                metadata: [
                    'error' => $e->getMessage(),
                ],
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to download application log.',
            ], 500);
        }
    }
}