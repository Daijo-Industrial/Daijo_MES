<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiWebhooks
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        // Sanitize payload to comply with GDPR & ISO 27001
        $payload = $request->except(['password', 'password_confirmation', 'api_token', 'token', 'authorization']);

        // Limit payload size to prevent excessive DB storage
        $payloadJson = json_encode($payload);
        if (strlen($payloadJson) > 5000) {
            $payload = ['summary' => substr($payloadJson, 0, 5000) . '... [TRUNCATED]'];
        }

        $isWebhook = str_contains($request->path(), 'webhook');
        $logName = $isWebhook ? 'webhook' : 'api';

        activity($logName)
            ->withProperties([
                'ip'          => $request->ip(),
                'method'      => $request->method(),
                'endpoint'    => '/' . ltrim($request->path(), '/'),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
                'payload'     => $payload,
            ])
            ->log("Incoming {$request->method()} /" . ltrim($request->path(), '/') . " ({$response->getStatusCode()})");

        return $response;
    }
}
