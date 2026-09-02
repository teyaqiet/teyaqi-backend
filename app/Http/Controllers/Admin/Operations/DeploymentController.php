<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\DeploymentService;
use App\Operations\Services\OperationAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DeploymentController extends Controller
{
    public function __construct(
        protected DeploymentService $deploymentService,
        protected OperationAuditService $auditService
    ) {}

    /**
     * Deployment overview.
     */
    public function overview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->deploymentService->overview(),
        ]);
    }

    /**
     * Deployment history.
     */
    public function index(Request $request): JsonResponse
    {
        $limit = min(
            max((int) $request->input('limit', 20), 1),
            100
        );

        return response()->json([
            'success' => true,
            'data' => $this->deploymentService->deployments($limit),
        ]);
    }

    /**
     * Show one deployment.
     */
    public function show(int $id): JsonResponse
    {
        $deployment = $this->deploymentService->find($id);

        if (!$deployment) {
            return response()->json([
                'success' => false,
                'message' => 'Deployment not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $deployment,
        ]);
    }

    /**
     * Run deployment preflight checks.
     */
    public function preflight(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->deploymentService->preflight(),
        ]);
    }

    /**
     * Queue/start a deployment.
     */
    public function deploy(Request $request): JsonResponse
    {
        try {
            $deployment = $this->deploymentService->createDeployment([
                'environment' => $request->input(
                    'environment',
                    config('operations.deployments.environment', 'staging')
                ),

                'branch' => $request->input(
                    'branch',
                    config('operations.deployments.branch', 'main')
                ),
            ]);

            $this->auditService->log(
                action: 'deployment.create',
                module: 'deployments',
                status: 'success',
                description: 'Deployment created.',
                metadata: [
                    'deployment_id' => $deployment->id,
                    'environment' => $deployment->environment,
                    'branch' => $deployment->branch,
                    'commit_hash' => $deployment->commit_hash,
                ],
            );

            return response()->json([
                'success' => true,
                'message' => 'Deployment has been queued.',
                'data' => $deployment,
            ], 201);

        } catch (Throwable $e) {

            $this->auditService->log(
                action: 'deployment.create',
                module: 'deployments',
                status: 'failed',
                description: 'Failed to create deployment.',
                metadata: [
                    'error' => $e->getMessage(),
                ],
            );

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    
}