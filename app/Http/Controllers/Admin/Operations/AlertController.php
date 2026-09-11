<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Models\OperationAlert;
use App\Operations\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct(
        protected AlertService $alertService,
    ) {}

    /**
     * Get alert statistics and active alerts.
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->input('status', 'active');
        $severity = $request->input('severity');

        $query = OperationAlert::query()
            ->latest('last_detected_at');

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'resolved') {
            $query->resolved();
        } elseif (in_array($status, [
            OperationAlert::STATUS_ACTIVE,
            OperationAlert::STATUS_ACKNOWLEDGED,
            OperationAlert::STATUS_RESOLVED,
        ], true)) {
            $query->where('status', $status);
        }

        if (in_array($severity, [
            OperationAlert::SEVERITY_INFO,
            OperationAlert::SEVERITY_WARNING,
            OperationAlert::SEVERITY_CRITICAL,
        ], true)) {
            $query->where('severity', $severity);
        }

        $alerts = $query
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $this->alertService->statistics(),
                'alerts' => $alerts,
            ],
        ]);
    }

    /**
     * Show a single alert.
     */
    public function show(int $id): JsonResponse
    {
        $alert = OperationAlert::query()
            ->with([
                'acknowledgedBy',
                'resolvedBy',
            ])
            ->find($id);

        if (! $alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alert not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $alert,
        ]);
    }

    /**
     * Acknowledge an active alert.
     */
    public function acknowledge(int $id): JsonResponse
    {
        $alert = OperationAlert::find($id);

        if (! $alert) {
            return response()->json([
                'success' => false,
                'message' => 'Alert not found.',
            ], 404);
        }

        if ($alert->isResolved()) {
            return response()->json([
                'success' => false,
                'message' => 'Resolved alerts cannot be acknowledged.',
            ], 422);
        }

        $adminId = auth('admin')->id();

        $alert = $this->alertService->acknowledge(
            $alert,
            $adminId
        );

        return response()->json([
            'success' => true,
            'message' => 'Alert acknowledged successfully.',
            'data' => $alert,
        ]);
    }

    /**
     * Resolve an alert.
     */
    
public function resolve(int $id): JsonResponse
{
    $alert = OperationAlert::find($id);

    if (! $alert) {
        return response()->json([
            'success' => false,
            'message' => 'Alert not found.',
        ], 404);
    }

    if ($alert->isResolved()) {
        return response()->json([
            'success' => false,
            'message' => 'Alert is already resolved.',
        ], 422);
    }

    $adminId = auth('admin')->id();

    $alert = $this->alertService->resolve(
        alert: $alert,
        adminId: $adminId,
        reason: 'Manually resolved by administrator.'
    );

    return response()->json([
        'success' => true,
        'message' => 'Alert resolved successfully.',
        'data' => $alert,
    ]);
}
}