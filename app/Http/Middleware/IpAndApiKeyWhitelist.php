<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IpAndApiKeyWhitelist
{
    public function handle(Request $request, Closure $next)
    {
        // Daftar IP yang di-whitelist
        $whitelistedIps = [
            '192.168.6.1', // sap
            // '192.168.6.100',
            // '192.168.6.149',
            // '127.0.0.1', // localhost IPv4 komputer lokal
            // '::1',       // localhost IPv6
        ];

        // Ambil IP pengirim
        $clientIp = $request->ip();

        // Cek apakah IP yang melakukan request ada dalam whitelist
        if (!in_array($clientIp, $whitelistedIps)) {
            return response()->json([
                'message' => 'Forbidden: IP not whitelisted',
                'client_ip' => $clientIp,
                'whitelisted_ips' => $whitelistedIps
            ], 403);
        }

        // Cek API Key dari header (toleran case & fallback query input)
        $apiKey = $request->header('X-API-KEY') 
            ?? $request->header('x-api-key') 
            ?? $request->input('api_key') 
            ?? $request->input('apiKey');

        $validApiKey = config('services.api_secret_key') ?: env('API_SECRET_KEY');

        if (empty($apiKey) || empty($validApiKey) || trim((string)$apiKey) !== trim((string)$validApiKey)) {
            return response()->json([
                'message' => 'Unauthorized: Invalid API Key',
                'client_ip' => $clientIp
            ], 401);
        }

        return $next($request);
    }
}
