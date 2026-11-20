<?php

namespace Tests\Unit;

use App\Services\TelegramAlertService;
use Tests\TestCase;

class TelegramAlertServiceTest extends TestCase
{
    public function test_send_message_returns_false_when_token_is_missing(): void
    {
        config(['services.telegram.bot_token' => null]);
        config(['services.telegram.chat_id' => '12345']);

        $sent = TelegramAlertService::sendMessage('Test message');
        $this->assertFalse($sent);
    }

    public function test_send_message_returns_false_when_chat_id_is_missing(): void
    {
        config(['services.telegram.bot_token' => 'dummy_token']);
        config(['services.telegram.chat_id' => null]);

        $sent = TelegramAlertService::sendMessage('Test message');
        $this->assertFalse($sent);
    }

    public function test_send_sap_timeout_alert_delegates_to_send_message(): void
    {
        // When config is empty, should safely return false without exception
        config(['services.telegram.bot_token' => '']);
        config(['services.telegram.chat_id' => '']);

        $sent = TelegramAlertService::sendSapTimeoutAlert(
            endpoint: '/api/receipt-production',
            errorMessage: 'cURL error 28: Operation timed out',
            logId: 999
        );

        $this->assertFalse($sent);
    }
}

