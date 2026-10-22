<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BaseSapService
{
    protected $baseUrl;

    protected $authUrl;

    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('services.sap.base_url') ?: 'http://192.168.6.149:9001';
        $this->authUrl = config('services.sap.auth_url') ?: 'http://192.168.6.149:9001/auth/token';
        // Jangan authenticate di constructor - lazy load aja
        $this->token = null;
    }

    /**
     * Get token - lazy load when needed
     */
    protected function getToken()
    {
        if ($this->token) {
            return $this->token;
        }

        // Try cache dulu
        $cachedToken = Cache::get('sap_token');
        if ($cachedToken) {
            $this->token = $cachedToken;

            return $this->token;
        }

        // Gunakan lock atomic untuk mencegah "cache stampede" / request auth tabrakan
        $lock = Cache::lock('sap_token_lock', 40); // Lock bertahan maks 40 detik

        try {
            // Blokir request lain selama max 30 detik untuk menunggu request pertama selesai
            if ($lock->block(30)) {
                // Check cache lagi setelah dapat lock (mungkin sudah dibuat oleh request lain)
                $cachedToken = Cache::get('sap_token');
                if ($cachedToken) {
                    $this->token = $cachedToken;

                    return $this->token;
                }

                // Kalau memang belum ada, baru authenticate
                $this->token = $this->authenticate();
                Cache::put('sap_token', $this->token, now()->addMinutes(50));

                return $this->token;
            }
        } catch (\Exception $e) {
            Log::error('SAP Token Lock / Auth Error: ' . $e->getMessage());
            // Fallback: coba authenticate langsung jika lock bermasalah
            $this->token = $this->authenticate();
            Cache::put('sap_token', $this->token, now()->addMinutes(50));

            return $this->token;
        } finally {
            optional($lock)->release();
        }

        return $this->token;
    }

    /**
     * Authenticate to SAP API and get the token
     */
    protected function authenticate()
    {
        try {
            $response = Http::withHeaders([
                'Host' => 'localhost',
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post($this->authUrl, [
                    'CompanyDB' => config('services.sap.company_db', env('SAP_COMPANY_DB')),
                    'Username' => config('services.sap.username', env('SAP_USERNAME')),
                    'Password' => config('services.sap.password', env('SAP_PASSWORD')),
                ]);

            if ($response->successful()) {
                Log::info('SAP Authentication successful');

                return $response->json()['access_token'];
            }

            Log::error('SAP Auth failed: ' . $response->status() . ' - ' . $response->body());
            throw new \Exception('Failed to authenticate to SAP: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('SAP Authentication error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send a GET request to SAP API
     */
    protected function get($endpoint, $params = [])
    {
        try {
            $token = $this->getToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Host' => 'localhost',
            ])
                ->timeout(120) // 120s timeout for large data reports (Line Production, Reject, etc.)
                ->connectTimeout(15)
                ->retry(2, 1000, throw: false)
                ->get($this->baseUrl . $endpoint, $params);

            if ($response && $response->successful()) {
                return $response->json();
            }

            // 401 = token expired
            if ($response && $response->status() === 401) {
                Log::warning('SAP token expired, refreshing...');
                Cache::forget('sap_token');
                $this->token = null;

                // Retry once with fresh token
                $token = $this->getToken();
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Host' => 'localhost',
                ])
                    ->timeout(120)
                    ->connectTimeout(15)
                    ->retry(2, 1000, throw: false)
                    ->get($this->baseUrl . $endpoint, $params);

                if ($response && $response->successful()) {
                    return $response->json();
                }
            }

            $status = $response ? $response->status() : 'TIMEOUT/NO_RESPONSE';
            $body = $response ? $response->body() : 'No response from SAP server';
            throw new \Exception("SAP GET Error [{$status}]: {$body}");
        } catch (\Exception $e) {
            Log::error("SAP GET request error ({$endpoint}): " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send a POST request to SAP API
     */
    protected function post($endpoint, $payload = [])
    {
        try {
            $token = $this->getToken();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Host' => 'localhost',
            ])
                ->timeout(120)
                ->connectTimeout(15)
                ->retry(2, 1000, throw: false)
                ->post($this->baseUrl . $endpoint, $payload);

            if ($response && $response->status() === 401) {
                Log::warning('SAP token expired during POST, refreshing...');
                Cache::forget('sap_token');
                $this->token = null;

                $token = $this->getToken();
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Host' => 'localhost',
                ])
                    ->timeout(120)
                    ->connectTimeout(15)
                    ->retry(2, 1000, throw: false)
                    ->post($this->baseUrl . $endpoint, $payload);
            }

            return $response;

        } catch (\Exception $e) {
            Log::error("SAP POST request error ({$endpoint}): " . $e->getMessage());
            throw $e;
        }
    }

    public function testGet($endpoint, $params = [])
    {
        return $this->get($endpoint, $params);
    }

    public function testPost()
    {
        return $this->post($this->endpoint, []);
    }

    public function saveApiLog($apiName, $method, $endpoint, $request, $response, $statusCode, $status, $message)
    {
        try {
            Cache::forget('api_log_names');
        } catch (\Throwable $e) {
        }

        \Illuminate\Support\Facades\DB::table('api_logs')->insert([
            'api_name' => $apiName,
            'method' => $method,
            'endpoint' => $endpoint,
            'request_payload' => is_string($request) ? $request : json_encode($request, JSON_PRETTY_PRINT),
            'response_payload' => is_string($response) ? $response : json_encode($response, JSON_PRETTY_PRINT),
            'status_code' => $statusCode,
            'status' => $status,
            'message' => $message,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
