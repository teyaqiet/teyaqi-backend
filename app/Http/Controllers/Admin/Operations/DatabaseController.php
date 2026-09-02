<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Http\Controllers\Controller;
use App\Operations\Services\DatabaseService;
use Illuminate\Http\JsonResponse;

class DatabaseController extends Controller
{
    public function __construct(
        protected DatabaseService $databaseService
    ) {}

    public function overview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->databaseService->overview(),
        ]);
    }

    public function tables(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->databaseService->tables(),
        ]);
    }

    public function migrations(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->databaseService->migrations(),
        ]);
    }
}
