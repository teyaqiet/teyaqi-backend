<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiLog;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $response = null;

        $requestData = array_merge($request->query(), $request->all());
        $sanitizedData = collect($requestData)->except([
            'password', 
            'password_confirmation', 
            'token'
        ])->toArray();

        $user = $request->user('sanctum');

        // Filter out noisy headers, keep what matters
        $allowedHeaders = ['user-agent', 'content-type', 'authorization', 'x-telegram-init-data'];
        $filteredHeaders = collect($request->headers->all())
            ->intersectByKeys(array_flip($allowedHeaders))
            ->map(function ($values, $key) {
                if ($key === 'authorization') {
                    return ['Bearer [MASKED]'];
                }
                return $values;
            })->toArray();

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            ApiLog::create([
                'causer_type' => $user ? get_class($user) : null,
                'causer_id' => $user ? $user->id : null,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip_address' => $request->ip(),
                'status_code' => $status,
                'request_headers' => $filteredHeaders,
                'request_payload' => !empty($sanitizedData) ? $sanitizedData : null,
                'response_body' => ['error' => $e->getMessage()],
                'response_time' => $responseTime,
            ]);

            throw $e;
        }

        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        $responseContent = $response->getContent();
        $decodedResponse = json_decode($responseContent, true);
        $responseBodyToStore = json_last_error() === JSON_ERROR_NONE ? $decodedResponse : $responseContent;

        ApiLog::create([
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user ? $user->id : null,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'status_code' => $response->getStatusCode(),
            'request_headers' => $filteredHeaders,
            'request_payload' => !empty($sanitizedData) ? $sanitizedData : null,
            'response_body' => $responseBodyToStore,
            'response_time' => $responseTime,
        ]);

        return $response;
    }
}