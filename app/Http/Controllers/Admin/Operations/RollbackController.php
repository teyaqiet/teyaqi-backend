<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Jobs\ExecuteRollbackJob;
use App\Models\OperationDeployment;
use App\Operations\Services\DeploymentLockService;
use App\Operations\Services\DeploymentRollbackService;
use App\Operations\Services\OperationAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RollbackController extends Controller
{
    public function __construct(
        protected DeploymentRollbackService $rollbackService,
        protected DeploymentLockService $lockService,
        protected OperationAuditService $auditService
    ) {
    }

    /**
     * Available rollback targets.
     */
    public function index(
        Request $request
    ): JsonResponse {
        $environment =
            $request->input(
                'environment',
                config(
                    'operations.deployments.environment',
                    'staging'
                )
            );

        $limit = min(
            max(
                (int) $request->input(
                    'limit',
                    20
                ),
                1
            ),
            100
        );

        return response()->json([
            'success' => true,

            'data' =>
                $this->rollbackService
                    ->availableDeployments(
                        $environment,
                        $limit
                    ),
        ]);
    }

    /**
     * Preview a rollback.
     */
    public function preview(
        int $id
    ): JsonResponse {
        $deployment =
            OperationDeployment::find($id);

        if (! $deployment) {
            return response()->json([
                'success' => false,

                'message' =>
                    'Deployment not found.',
            ], 404);
        }

        try {
            return response()->json([
                'success' => true,

                'data' =>
                    $this->rollbackService
                        ->preview(
                            $deployment
                        ),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,

                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Start a rollback.
     */
    public function create(
        Request $request,
        int $id
    ): JsonResponse {
        try {
            $target =
                OperationDeployment::find($id);

            if (! $target) {
                return response()->json([
                    'success' => false,

                    'message' =>
                        'Deployment not found.',
                ], 404);
            }

            $environment =
                $target->environment;

            $lock =
                $this->lockService->current(
                    $environment
                );

            if ($lock) {
                return response()->json([
                    'success' => false,

                    'message' =>
                        "Deployment #{$lock->deployment_id} is already running in {$environment}.",

                    'data' => [
                        'locked' => true,

                        'deployment_id' =>
                            $lock->deployment_id,

                        'environment' =>
                            $lock->environment,

                        'locked_at' =>
                            $lock->locked_at,

                        'expires_at' =>
                            $lock->expires_at,
                    ],
                ], 409);
            }

            $deployment =
                $this->rollbackService
                    ->createRollback(
                        $target,
                        auth('admin')->id()
                    );

            $this->auditService->log(
                action: 'rollback.create',

                module: 'deployments',

                status: 'success',

                description:
                    'Rollback created.',

                metadata: [
                    'rollback_deployment_id' =>
                        $deployment->id,

                    'target_deployment_id' =>
                        $target->id,

                    'target_commit' =>
                        $target->commit_hash,
                ],
            );

            ExecuteRollbackJob::dispatch(
                $deployment->id
            );

            return response()->json([
                'success' => true,

                'message' =>
                    "Rollback to deployment #{$target->id} has been queued.",

                'data' =>
                    $deployment->fresh(),
            ], 201);
        } catch (Throwable $e) {
            $this->auditService->log(
                action: 'rollback.create',

                module: 'deployments',

                status: 'failed',

                description:
                    'Failed to create rollback.',

                metadata: [
                    'deployment_id' =>
                        $id,

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
}