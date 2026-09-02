<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\CacheService;
use App\Operations\Services\OperationAuditService;
use Illuminate\Http\JsonResponse;

class CacheController extends Controller
{
    public function __construct(
        protected CacheService $cacheService,
        protected OperationAuditService $auditService
    ) {}

    public function overview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->cacheService->overview(),
        ]);
    }

    public function clear(): JsonResponse
    {
        return $this->executeCacheAction(
            action: 'cache.clear',
            description: 'Application cache cleared.',
            callback: fn () => $this->cacheService->clearApplicationCache()
        );
    }

    public function clearConfig(): JsonResponse
    {
        return $this->executeCacheAction(
            action: 'cache.config.clear',
            description: 'Configuration cache cleared.',
            callback: fn () => $this->cacheService->clearConfigCache()
        );
    }

    public function clearRoutes(): JsonResponse
    {
        return $this->executeCacheAction(
            action: 'cache.route.clear',
            description: 'Route cache cleared.',
            callback: fn () => $this->cacheService->clearRouteCache()
        );
    }

    public function clearViews(): JsonResponse
    {
        return $this->executeCacheAction(
            action: 'cache.view.clear',
            description: 'View cache cleared.',
            callback: fn () => $this->cacheService->clearViewCache()
        );
    }

    public function clearAll(): JsonResponse
    {
        return $this->executeCacheAction(
            action: 'cache.all.clear',
            description: 'All supported application caches cleared.',
            callback: fn () => $this->cacheService->clearAllCaches()
        );
    }

    protected function executeCacheAction(
        string $action,
        string $description,
        callable $callback
    ): JsonResponse {
        $result = $callback();

        if (! $result['success']) {
            $this->auditService->failed(
                action: $action,
                module: 'cache',
                description: $description,
                metadata: $result,
            );

            return response()->json([
                'success' => false,
                'message' => 'Cache operation failed.',
                'data' => $result,
            ], 500);
        }

        $this->auditService->success(
            action: $action,
            module: 'cache',
            description: $description,
            metadata: $result,
        );

        return response()->json([
            'success' => true,
            'message' => 'Cache operation completed successfully.',
            'data' => $result,
        ]);
    }
}