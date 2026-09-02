<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Illuminate\Http\Request;

class ApiLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ApiLog::with('causer')->latest();

        if ($request->filled('search')) {
            $query->where('url', 'like', "%{$request->search}%");
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('status')) {
            $query->where('response_status', $request->status); // Fixed from status_code to response_status
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.system.api-logs', compact('logs'));
    }

    public function show(ApiLog $apiLog)
    {
        return view('admin.system.api-log-detail', compact('apiLog'));
    }

    public function export(Request $request)
{
    $fileName = 'api-logs-' . date('Y-m-d') . '.csv';
    
    $logs = ApiLog::with('causer')->latest()->get();

    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $callback = function() use($logs) {
        $file = fopen('php://output', 'w');
        fputcsv($file, ['ID', 'Method', 'URL', 'Status', 'Response Time (ms)', 'Causer', 'Timestamp']);

        foreach ($logs as $log) {
            fputcsv($file, [
                $log->id,
                $log->method,
                $log->url,
                $log->status_code,
                $log->response_time,
                $log->causer->name ?? 'Guest',
                $log->created_at,
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}
}