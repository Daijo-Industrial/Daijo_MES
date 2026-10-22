<?php

namespace Tests\Feature;

use App\Services\BaseSapService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BaseSapServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('sap_token');
        Cache::forget('sap_token_lock');
    }

    public function test_service_initializes_with_configured_urls()
    {
        config([
            'services.sap.base_url' => 'http://test-sap-server:9001',
            'services.sap.auth_url' => 'http://test-sap-server:9001/auth/token',
            'services.sap.company_db' => 'TEST_DB',
            'services.sap.username' => 'test_user',
            'services.sap.password' => 'test_pass',
        ]);

        $service = new BaseSapService;

        // Use reflection to inspect protected properties
        $reflector = new \ReflectionClass($service);
        $baseUrlProp = $reflector->getProperty('baseUrl');
        $baseUrlProp->setAccessible(true);
        $authUrlProp = $reflector->getProperty('authUrl');
        $authUrlProp->setAccessible(true);
        $tokenProp = $reflector->getProperty('token');
        $tokenProp->setAccessible(true);

        $this->assertEquals('http://test-sap-server:9001', $baseUrlProp->getValue($service));
        $this->assertEquals('http://test-sap-server:9001/auth/token', $authUrlProp->getValue($service));
        $this->assertNull($tokenProp->getValue($service), 'Token should be lazy-loaded, not initialized in constructor');
    }

    public function test_service_authenticates_using_configured_credentials()
    {
        config([
            'services.sap.base_url' => 'http://test-sap-server:9001',
            'services.sap.auth_url' => 'http://test-sap-server:9001/auth/token',
            'services.sap.company_db' => 'CONFIG_COMPANY_DB',
            'services.sap.username' => 'config_username',
            'services.sap.password' => 'config_password',
        ]);

        Http::fake([
            'http://test-sap-server:9001/auth/token' => function (\Illuminate\Http\Client\Request $request) {
                $payload = $request->data();
                if (
                    ($payload['CompanyDB'] ?? '') === 'CONFIG_COMPANY_DB' &&
                    ($payload['Username'] ?? '') === 'config_username' &&
                    ($payload['Password'] ?? '') === 'config_password'
                ) {
                    return Http::response(['access_token' => 'mocked-jwt-token-123'], 200);
                }

                return Http::response(['error' => 'Invalid credentials'], 401);
            },
            'http://test-sap-server:9001/api/test' => Http::response(['data' => ['success' => true]], 200),
        ]);

        $service = new BaseSapService;
        $data = $service->testGet('/api/test');

        $this->assertEquals(['data' => ['success' => true]], $data);
        $this->assertEquals('mocked-jwt-token-123', Cache::get('sap_token'));

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === 'http://test-sap-server:9001/auth/token' &&
                   $request['CompanyDB'] === 'CONFIG_COMPANY_DB' &&
                   $request['Username'] === 'config_username' &&
                   $request['Password'] === 'config_password';
        });
    }

    public function test_service_reuses_cached_token()
    {
        Cache::put('sap_token', 'pre-existing-token', now()->addMinutes(50));

        Http::fake([
            '*/api/test' => Http::response(['status' => 'ok'], 200),
        ]);

        $service = new BaseSapService;
        $result = $service->testGet('/api/test');

        $this->assertEquals(['status' => 'ok'], $result);

        // No auth request should have been made
        Http::assertNotSent(function (\Illuminate\Http\Client\Request $request) {
            return str_contains($request->url(), '/auth/token');
        });

        // The Bearer token in the GET request must match the cached token
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->hasHeader('Authorization', 'Bearer pre-existing-token');
        });
    }

    public function test_service_refreshes_token_on_401_response()
    {
        Cache::put('sap_token', 'stale-token', now()->addMinutes(50));

        $attempt = 0;
        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'fresh-token-456'], 200),
            '*/api/retry-test' => function (\Illuminate\Http\Client\Request $request) use (&$attempt) {
                $attempt++;
                if ($request->hasHeader('Authorization', 'Bearer stale-token')) {
                    return Http::response(['error' => 'Token expired'], 401);
                }
                if ($request->hasHeader('Authorization', 'Bearer fresh-token-456')) {
                    return Http::response(['status' => 'recovered'], 200);
                }

                return Http::response(['error' => 'Unknown'], 500);
            },
        ]);

        $service = new BaseSapService;
        $result = $service->testGet('/api/retry-test');

        $this->assertEquals(['status' => 'recovered'], $result);
        $this->assertEquals('fresh-token-456', Cache::get('sap_token'));
        // Initial request retried due to retry(2) on 401 + 1 final attempt with refreshed token = 3
        $this->assertEquals(3, $attempt);
    }

    public function test_save_api_log_persists_entry_to_database()
    {
        $service = new BaseSapService;
        $apiName = 'TEST_API_' . uniqid();

        $service->saveApiLog(
            $apiName,
            'POST',
            '/api/test/endpoint',
            ['key' => 'request_val'],
            ['key' => 'response_val'],
            200,
            'success',
            'Test message'
        );

        $this->assertDatabaseHas('api_logs', [
            'api_name' => $apiName,
            'method' => 'POST',
            'endpoint' => '/api/test/endpoint',
            'status_code' => 200,
            'status' => 'success',
            'message' => 'Test message',
        ]);
    }
}
