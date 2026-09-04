<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\DeploymentLockService;
use App\Operations\Services\DeploymentService;
use App\Operations\Services\OperationAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DeploymentController extends Controller
{
    public function __construct(
        protected DeploymentService $deploymentService,
        protected OperationAuditService $auditService,
        protected DeploymentLockService $deploymentLockService
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
            /*
             * -------------------------------------------------
             * DEPLOYMENT INPUT
             * -------------------------------------------------
             */

            $environment = $request->input(
                'environment',
                config(
                    'operations.deployments.environment',
                    'staging'
                )
            );

            $branch = $request->input(
                'branch',
                config(
                    'operations.deployments.branch',
                    'main'
                )
            );

            /*
             * -------------------------------------------------
             * CHECK DEPLOYMENT LOCK
             * -------------------------------------------------
             *
             * Prevent another deployment from being created
             * while this environment is already locked.
             *
             * The queued job also acquires the lock again
             * when execution begins. That second check is
             * the final server-side protection against
             * concurrent deployments.
             */
            $existingLock =
                $this->deploymentLockService->current(
                    $environment
                );

            if ($existingLock) {
                /*
                 * Record the rejected deployment attempt.
                 */
                $this->auditService->log(
                    action: 'deployment.create',
                    module: 'deployments',
                    status: 'failed',
                    description: 'Deployment rejected because another deployment is already running.',
                    metadata: [
                        'environment' => $environment,
                        'branch' => $branch,
                        'existing_deployment_id' =>
                            $existingLock->deployment_id,
                        'lock_id' =>
                            $existingLock->id,
                        'locked_at' =>
                            $existingLock->locked_at,
                        'expires_at' =>
                            $existingLock->expires_at,
                    ],
                );

                return response()->json([
                    'success' => false,

                    'message' =>
                        "Deployment #{$existingLock->deployment_id} is already running in {$environment}.",

                    'data' => [
                        'locked' => true,

                        'deployment_id' =>
                            $existingLock->deployment_id,

                        'environment' =>
                            $existingLock->environment,

                        'locked_at' =>
                            $existingLock->locked_at,

                        'expires_at' =>
                            $existingLock->expires_at,
                    ],
                ], 409);
            }

            /*
             * -------------------------------------------------
             * CREATE DEPLOYMENT
             * -------------------------------------------------
             */

            $deployment =
                $this->deploymentService->createDeployment([
                    'environment' => $environment,
                    'branch' => $branch,
                ]);

            /*
             * -------------------------------------------------
             * AUDIT SUCCESS
             * -------------------------------------------------
             */

            $this->auditService->log(
                action: 'deployment.create',
                module: 'deployments',
                status: 'success',
                description: 'Deployment created.',
                metadata: [
                    'deployment_id' =>
                        $deployment->id,

                    'environment' =>
                        $deployment->environment,

                    'branch' =>
                        $deployment->branch,

                    'commit_hash' =>
                        $deployment->commit_hash,
                ],
            );

            /*
             * -------------------------------------------------
             * RESPONSE
             * -------------------------------------------------
             */

            return response()->json([
                'success' => true,

                'message' =>
                    'Deployment has been queued.',

                'data' =>
                    $deployment,
            ], 201);

        } catch (Throwable $e) {

            /*
             * -------------------------------------------------
             * AUDIT FAILURE
             * -------------------------------------------------
             */

            $this->auditService->log(
                action: 'deployment.create',
                module: 'deployments',
                status: 'failed',
                description: 'Failed to create deployment.',
                metadata: [
                    'error' =>
                        $e->getMessage(),
                ],
            );

            return response()->json([
                'success' => false,

                'message' =>
                    $e->getMessage(),

            ], 422);
        }
    }

    /**
     * Get current deployment lock status.
     */
    public function lockStatus(
        Request $request
    ): JsonResponse {
        $environment = $request->input(
            'environment',
            config(
                'operations.deployments.environment',
                'staging'
            )
        );

        $lock =
            $this->deploymentLockService->current(
                $environment
            );

        return response()->json([
            'success' => true,

            'data' => [
                'locked' =>
                    $lock !== null,

                'lock' =>
                    $lock,
            ],
        ]);
    }
}