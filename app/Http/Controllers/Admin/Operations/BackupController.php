<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\BackupService;
use App\Operations\Services\OperationAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class BackupController extends Controller
{
    public function __construct(
        protected BackupService $backupService,
        protected OperationAuditService $auditService
    ) {}

    public function overview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->backupService->overview(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(
            max(
                (int) $request->input(
                    'per_page',
                    25
                ),
                1
            ),
            100
        );

        return response()->json([
            'success' => true,
            'data' => $this->backupService->list(
                $perPage
            ),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $backup = $this->backupService->find($id);

        if (! $backup) {
            return response()->json([
                'success' => false,
                'message' => 'Backup not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $backup,
        ]);
    }

    public function create(): JsonResponse
{
    try {
        $backup = $this->backupService
            ->createDatabaseBackup(
                auth('admin')->id()
            );

        $this->auditService->success(
            action: 'backup.create',
            module: 'backup',
            description: 'Database backup queued.',
            metadata: [
                'backup_id' => $backup->id,
                'filename' => $backup->filename,
                'status' => $backup->status,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Database backup has been queued.',
            'data' => $backup,
        ], 202);

    } catch (Throwable $e) {

        $this->auditService->failed(
            action: 'backup.create',
            module: 'backup',
            description: 'Database backup could not be queued.',
            metadata: [
                'error' => $e->getMessage(),
            ],
        );

        return response()->json([
            'success' => false,
            'message' => 'Unable to queue database backup.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function download(int $id)
    {
        $backup = $this->backupService->find($id);

        if (! $backup) {
            abort(404);
        }

        $disk = Storage::disk(
            $backup->disk
        );

        if (! $disk->exists($backup->path)) {
            abort(404);
        }

        $this->auditService->success(
            action: 'backup.download',
            module: 'backup',
            description: 'Database backup downloaded.',
            metadata: [
                'backup_id' => $backup->id,
                'filename' => $backup->filename,
            ],
        );

        return $disk->download(
            $backup->path,
            $backup->filename
        );
    }

    public function delete(int $id): JsonResponse
    {
        $backup = $this->backupService->find($id);

        if (! $backup) {
            return response()->json([
                'success' => false,
                'message' => 'Backup not found.',
            ], 404);
        }

        try {

            $filename = $backup->filename;

            $this->backupService->delete(
                $backup
            );

            $this->auditService->success(
                action: 'backup.delete',
                module: 'backup',
                description: 'Database backup deleted.',
                metadata: [
                    'backup_id' => $id,
                    'filename' => $filename,
                ],
            );

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully.',
            ]);

        } catch (Throwable $e) {

            $this->auditService->failed(
                action: 'backup.delete',
                module: 'backup',
                description: 'Database backup deletion failed.',
                metadata: [
                    'backup_id' => $id,
                    'error' => $e->getMessage(),
                ],
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete backup.',
            ], 500);
        }
    }
}