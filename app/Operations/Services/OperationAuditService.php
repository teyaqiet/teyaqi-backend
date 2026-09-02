<?php

namespace App\Operations\Services;

use App\Models\OperationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class OperationAuditService
{
    public function log(
        string $action,
        ?string $module = null,
        string $status = 'success',
        ?string $description = null,
        array $metadata = []
    ): OperationLog {
        return OperationLog::create([
            'admin_user_id' => Auth::guard('admin')->id(),

            'action' => $action,

            'module' => $module,

            'environment' => config(
                'operations.environment',
                config('app.env')
            ),

            'status' => $status,

            'description' => $description,

            'metadata' => $metadata,

            'ip_address' => Request::ip(),

            'user_agent' => Request::userAgent(),
        ]);
    }

    public function success(
        string $action,
        ?string $module = null,
        ?string $description = null,
        array $metadata = []
    ): OperationLog {
        return $this->log(
            action: $action,
            module: $module,
            status: 'success',
            description: $description,
            metadata: $metadata,
        );
    }

    public function failed(
        string $action,
        ?string $module = null,
        ?string $description = null,
        array $metadata = []
    ): OperationLog {
        return $this->log(
            action: $action,
            module: $module,
            status: 'failed',
            description: $description,
            metadata: $metadata,
        );
    }

    public function list(
        int $perPage = 25,
        ?string $module = null,
        ?string $action = null,
        ?string $status = null,
        ?string $environment = null,
        ?string $search = null,
    ): LengthAwarePaginator {
        return OperationLog::query()
            ->with('adminUser:id,name,email')

            ->when(
                $module,
                fn ($query) =>
                    $query->where('module', $module)
            )

            ->when(
                $action,
                fn ($query) =>
                    $query->where('action', $action)
            )

            ->when(
                $status,
                fn ($query) =>
                    $query->where('status', $status)
            )

            ->when(
                $environment,
                fn ($query) =>
                    $query->where('environment', $environment)
            )

            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where(
                            'action',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'adminUser',
                            function ($query) use ($search) {
                                $query
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'email',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                });
            })

            ->latest()

            ->paginate($perPage);
    }

    public function find(int $id): ?OperationLog
    {
        return OperationLog::query()
            ->with('adminUser:id,name,email')
            ->find($id);
    }
}