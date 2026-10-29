<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramAlertService
{
    /**
     * Kirim pesan teks Markdown ke Telegram Bot (mendukung specific topic / thread ID).
     */
    public static function sendMessage(string $message, ?string $chatId = null, ?int $topicId = null): bool
    {
        $token = config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN'));
        $targetChatId = $chatId ?: config('services.telegram.chat_id', env('TELEGRAM_ALERT_CHAT_ID'));
        $targetTopicId = $topicId !== null ? $topicId : config('services.telegram.topic_id', env('TELEGRAM_ALERT_TOPIC_ID'));

        if (empty($token) || empty($targetChatId)) {
            Log::warning('Telegram alert skipped: Token atau Chat ID belum dikonfigurasi.');
            return false;
        }

        try {
            $payload = [
                'chat_id' => $targetChatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true,
            ];

            if (! empty($targetTopicId)) {
                $payload['message_thread_id'] = (int) $targetTopicId;
            }

            $response = Http::timeout(8)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

            if ($response->successful()) {
                Log::info("Telegram alert berhasil terkirim ke chat ID: {$targetChatId}" . ($targetTopicId ? " (Topic ID: {$targetTopicId})" : ''));
                return true;
            }

            // Auto-heal jika grup di-upgrade ke supergroup
            $json = $response->json();
            if (isset($json['parameters']['migrate_to_chat_id'])) {
                $newChatId = $json['parameters']['migrate_to_chat_id'];
                Log::warning("Telegram group chat di-upgrade ke supergroup {$newChatId}. Mengirim ulang...");

                $payload['chat_id'] = $newChatId;
                $retry = Http::timeout(8)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
                if ($retry->successful()) {
                    Log::info("Telegram alert berhasil terkirim ke new supergroup chat ID: {$newChatId}");
                    return true;
                }
            }

            Log::error('Telegram alert gagal dikirim: ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('Telegram alert exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim format pesan standar untuk SAP Timeout Alert.
     */
    public static function sendSapTimeoutAlert(string $endpoint, string $errorMessage, ?int $logId = null, ?int $topicId = null): bool
    {
        $timeStr = now('Asia/Jakarta')->format('d M Y H:i:s');
        $idStr = $logId ? " #{$logId}" : '';
        $shortError = Str::limit($errorMessage, 200);

        $text = "🚨 *[ALERT] SAP API TIMEOUT - DAIJO MES*\n\n"
            . "⚠️ Terdeteksi kegagalan koneksi (*cURL 28: Operation timed out*) saat integrasi *Receipt Production* ke SAP.\n\n"
            . "📌 *Detail Kejadian:*\n"
            . "• *Waktu:* `{$timeStr} WIB`\n"
            . "• *API Endpoint:* `{$endpoint}`\n"
            . ($idStr ? "• *Log ID:* `{$idStr}`\n" : '')
            . "• *Target Gateway:* `" . config('services.sap.base_url', 'http://192.168.6.149:9001') . "`\n\n"
            . "📝 *Pesan Error:*\n"
            . "```\n{$shortError}\n```\n\n"
            . "🛠️ *Tindakan Disarankan:*\n"
            . "1. Periksa apakah server SAP / service API wrapper aktif.\n"
            . "2. Cek beban antrean / CPU Server SAP Business One.\n"
            . "3. Pastikan routing jaringan MES -> SAP stabil.\n\n"
            . "🔗 [Buka API Logs Dashboard](" . url('/api-log-dashboard') . ")";

        return self::sendMessage($text, null, $topicId);
    }
}
