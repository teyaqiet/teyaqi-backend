<?php

namespace App\Http\Controllers;

use App\Operations\Services\ApplicationHealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        protected ApplicationHealthService $healthService,
    ) {}

    public function check(): JsonResponse
    {
        $checks = $this->healthService->check();

        $healthy = collect($checks)
            ->every(
                fn (array $check) => $check['healthy'] === true
            );

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
