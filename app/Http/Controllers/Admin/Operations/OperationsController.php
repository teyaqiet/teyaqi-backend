<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\HealthService;
use App\Operations\Services\SystemService;
use Illuminate\Http\JsonResponse;

class OperationsController extends Controller
{
    public function __construct(
        protected HealthService $healthService,
        protected SystemService $systemService
    ) {}

    /**
     * Operations health overview.
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->healthService->check(),
        ]);
    }

    /**
     * Application environment information.
     */
    public function environment(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'application' => config(
                    'operations.application.name'
                ),

                'environment' => config(
                    'operations.environment',
                    config('app.env')
                ),

                'laravel' => app()->version(),

                'php' => PHP_VERSION,

                'debug' => config('app.debug'),

                'timezone' => config('app.timezone'),

                'locale' => config('app.locale'),

                'maintenance' => app()->isDownForMaintenance(),
            ],
        ]);
    }

    /**
     * Server and runtime information.
     */
    public function system(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->systemService->information(),
        ]);
    }
}
