<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\OperationAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        protected OperationAuditService $auditService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max(
                (int) $request->input('per_page', 25),
                1
            ),
            100
        );

        $logs = $this->auditService->list(
            perPage: $perPage,
            module: $request->input('module'),
            action: $request->input('action'),
            status: $request->input('status'),
            environment: $request->input('environment'),
            search: $request->input('search'),
        );

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $log = $this->auditService->find($id);

        if (! $log) {
            return response()->json([
                'success' => false,
                'message' => 'Operation log not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }
}