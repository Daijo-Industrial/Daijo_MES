<?php

namespace App\Services;

class SapErrorParser
{
    /**
     * Categorize and parse raw SAP error messages / API log messages into user-friendly Indonesian summaries.
     *
     * @param string|null $rawMessage
     * @param int|null $statusCode
     * @param string|null $spkCode
     * @return array
     */
    public static function parse(?string $rawMessage, ?int $statusCode = null, ?string $spkCode = null): array
    {
        if (empty($rawMessage)) {
            return self::buildResult(
                category: 'unknown',
                badgeLabel: 'Error Tidak Diketahui',
                badgeBg: '#F3F4F6',
                badgeColor: '#4B5563',
                badgeBorder: '#E5E7EB',
                icon: '❓',
                title: 'Tidak Ada Detail Pesan Error',
                userMessage: 'Gagal mengirim data ke SAP, namun tidak ada pesan detail yang dikembalikan oleh server gateway.',
                actionRecommendation: 'Coba klik "🚀 Kirim SAP" untuk mengirim ulang atau hubungi tim IT jika kendala berlanjut.',
                rawMessage: $rawMessage ?: '',
                statusCode: $statusCode,
                spkCode: $spkCode
            );
        }

        $cleanMsg = self::extractCleanMessage($rawMessage);
        $lower = strtolower($rawMessage . ' ' . $cleanMsg);

        // 1. Defisit Stok Komponen di SAP (Error [-5002] / Inventory to fall below zero)
        if (
            str_contains($lower, '-5002') ||
            str_contains($lower, 'fall below zero') ||
            str_contains($lower, 'consumed quantity') ||
            str_contains($lower, 'insufficient stock') ||
            str_contains($lower, 'stok tidak cukup')
        ) {
            $bomLine = self::extractBomLine($rawMessage);
            $lineNote = $bomLine ? " pada baris komponen {$bomLine}" : "";

            return self::buildResult(
                category: 'stock_deficit',
                badgeLabel: 'Stok Komponen Minus',
                badgeBg: '#FEE2E2',
                badgeColor: '#991B1B',
                badgeBorder: '#FECACA',
                icon: '📦',
                title: 'Stok Bahan Baku di SAP Kurang (Minus)',
                userMessage: "SAP menolak penerimaan hasil produksi karena stok material/komponen{$lineNote} di gudang SAP tidak mencukupi (akan menjadi minus jika dipotong backflush otomatis).",
                actionRecommendation: 'Tim Store / PPIC perlu melakukan Goods Issue (Issue for Production) atau transfer stok bahan baku untuk SPK ini di SAP Business One terlebih dahulu.',
                rawMessage: $rawMessage,
                statusCode: $statusCode,
                spkCode: $spkCode,
                errorCode: 'SAP-5002',
                bomLine: $bomLine
            );
        }

        // 2. Timeout Koneksi / cURL Error 28 / Gateway Tak Merespons
        if (
            str_contains($lower, 'curl error 28') ||
            str_contains($lower, 'timed out') ||
            str_contains($lower, 'timeout') ||
            str_contains($lower, 'operation timed out')
        ) {
            return self::buildResult(
                category: 'timeout',
                badgeLabel: 'Koneksi Timeout',
                badgeBg: '#FEF3C7',
                badgeColor: '#92400E',
                badgeBorder: '#FDE68A',
                icon: '🌐',
                title: 'Koneksi ke Server SAP Timeout',
                userMessage: 'Server SAP Gateway (192.168.6.149) tidak merespons dalam batas waktu timeout (server lambat, antrean padat, atau jaringan sedang tidak stabil).',
                actionRecommendation: 'Tunggu 1-2 menit lalu klik tombol "🚀 Kirim SAP" untuk mengirim ulang.',
                rawMessage: $rawMessage,
                statusCode: $statusCode ?: 504,
                spkCode: $spkCode,
                errorCode: 'TIMEOUT-28'
            );
        }

        // 3. Masalah Autentikasi / Gateway DI-API Crash / COM Object
        if (
            str_contains($lower, 'failed to authenticate') ||
            str_contains($lower, 'unable to cast com object') ||
            str_contains($lower, 'queryinterface') ||
            str_contains($lower, 'type_e_unsupformat') ||
            str_contains($lower, '0x80028019') ||
            str_contains($lower, 'unauthorized') ||
            str_contains($lower, 'invalid token') ||
            $statusCode === 401
        ) {
            return self::buildResult(
                category: 'gateway_auth',
                badgeLabel: 'Gateway SAP Bermasalah',
                badgeBg: '#F3E8FF',
                badgeColor: '#6B21A8',
                badgeBorder: '#DDD6FE',
                icon: '🔑',
                title: 'Gangguan Layanan DI-API / Gateway SAP',
                userMessage: 'Layanan penghubung SAP Business One (DI-API / Daijo API Gateway) di server Windows mengalami crash atau lock antarmuka COM.',
                actionRecommendation: 'Hubungi tim IT / Administrator SAP untuk me-restart service "Daijo API" di server SAP.',
                rawMessage: $rawMessage,
                statusCode: $statusCode ?: 401,
                spkCode: $spkCode,
                errorCode: 'GATEWAY-COM'
            );
        }

        // 4. Data Scan Produksi Belum Ada di MES
        if (
            str_contains($lower, 'scanned data not found') ||
            str_contains($lower, 'data scan tidak ditemukan')
        ) {
            return self::buildResult(
                category: 'no_scan',
                badgeLabel: 'Data Scan Kosong',
                badgeBg: '#FFFBEB',
                badgeColor: '#B45309',
                badgeBorder: '#FDE68A',
                icon: '⚠️',
                title: 'Data Scan Box Kosong di MES',
                userMessage: 'Tidak ditemukan riwayat scan barcode box produksi untuk nomor SPK ini di sistem MES.',
                actionRecommendation: 'Pastikan operator produksi telah melakukan scan barcode pada box SPK ini.',
                rawMessage: $rawMessage,
                statusCode: $statusCode ?: 404,
                spkCode: $spkCode,
                errorCode: 'MES-NO-SCAN'
            );
        }

        // 5. SPK Sudah Ada di SAP / Duplikat
        if (
            str_contains($lower, 'already exist') ||
            str_contains($lower, 'duplicate') ||
            str_contains($lower, 'sudah ada')
        ) {
            return self::buildResult(
                category: 'duplicate',
                badgeLabel: 'Sudah di SAP (Duplikat)',
                badgeBg: '#DCFCE7',
                badgeColor: '#15803D',
                badgeBorder: '#86EFAC',
                icon: '✓',
                title: 'SPK Sudah Terdaftar di SAP',
                userMessage: 'Dokumen Receipt Production untuk SPK ini sebenarnya sudah berhasil terbentuk di SAP sebelumnya.',
                actionRecommendation: 'Klik tombol "✓ Tandai Sukses (Sudah di SAP)" untuk mengupdate status di MES tanpa mengirim ulang.',
                rawMessage: $rawMessage,
                statusCode: $statusCode ?: 200,
                spkCode: $spkCode,
                errorCode: 'SAP-DUPLICATE'
            );
        }

        // 6. Status SPK Ditutup / Tidak Valid di SAP
        if (
            str_contains($lower, 'is closed') ||
            str_contains($lower, 'status is closed') ||
            str_contains($lower, 'canceled') ||
            str_contains($lower, 'cancelled') ||
            str_contains($lower, 'does not exist') ||
            str_contains($lower, 'not in released status')
        ) {
            return self::buildResult(
                category: 'spk_status',
                badgeLabel: 'Status SPK Tidak Valid',
                badgeBg: '#FEE2E2',
                badgeColor: '#B91C1C',
                badgeBorder: '#FECACA',
                icon: '🚫',
                title: 'Status SPK di SAP Tidak Valid / Ditutup',
                userMessage: 'Production Order di SAP sudah berstatus Closed / Canceled atau belum dalam status Released.',
                actionRecommendation: 'Periksa status Production Order untuk nomor SPK ini di aplikasi SAP Business One.',
                rawMessage: $rawMessage,
                statusCode: $statusCode,
                spkCode: $spkCode,
                errorCode: 'SAP-ORDER-STATUS'
            );
        }

        // 7. Periode Akuntansi SAP Ditutup (Period Closed)
        if (
            str_contains($lower, 'period is closed') ||
            str_contains($lower, 'posting date is not within') ||
            str_contains($lower, 'locked period')
        ) {
            return self::buildResult(
                category: 'period_closed',
                badgeLabel: 'Periode Posting Tutup',
                badgeBg: '#FEE2E2',
                badgeColor: '#B91C1C',
                badgeBorder: '#FECACA',
                icon: '📅',
                title: 'Periode Posting SAP Sudah Ditutup',
                userMessage: 'Tanggal transaksi berada pada periode akuntansi SAP yang sudah dikunci (Closed Period).',
                actionRecommendation: 'Hubungi tim Accounting / Finance untuk mengecek atau membuka periode posting terkait di SAP.',
                rawMessage: $rawMessage,
                statusCode: $statusCode,
                spkCode: $spkCode,
                errorCode: 'SAP-PERIOD-CLOSED'
            );
        }

        // 8. Gudang Terkunci / Tidak Valid di SAP
        if (
            str_contains($lower, 'warehouse is locked') ||
            str_contains($lower, 'invalid warehouse')
        ) {
            return self::buildResult(
                category: 'warehouse_error',
                badgeLabel: 'Gudang Terkunci',
                badgeBg: '#FEE2E2',
                badgeColor: '#B91C1C',
                badgeBorder: '#FECACA',
                icon: '🏢',
                title: 'Gudang Tujuan Tidak Valid / Terkunci di SAP',
                userMessage: 'Kode gudang penerimaan (FFI / KRFFI) tidak aktif atau sedang dikunci pada master data SAP.',
                actionRecommendation: 'Periksa master data gudang di SAP Business One.',
                rawMessage: $rawMessage,
                statusCode: $statusCode,
                spkCode: $spkCode,
                errorCode: 'SAP-WHS-ERROR'
            );
        }

        // 9. Error Umum / Fallback
        return self::buildResult(
            category: 'general',
            badgeLabel: 'Error SAP: ' . ($statusCode ? "HTTP {$statusCode}" : 'Gagal'),
            badgeBg: '#FEE2E2',
            badgeColor: '#B91C1C',
            badgeBorder: '#FECACA',
            icon: '⚠️',
            title: 'Ditolak oleh SAP Gateway',
            userMessage: $cleanMsg ?: 'SAP Gateway mengembalikan status gagal untuk pengiriman SPK ini.',
            actionRecommendation: 'Periksa detail pesan teknis SAP di bawah atau hubungi tim IT jika kendala berulang.',
            rawMessage: $rawMessage,
            statusCode: $statusCode,
            spkCode: $spkCode,
            errorCode: $statusCode ? "HTTP-{$statusCode}" : 'GENERAL'
        );
    }

    /**
     * Extract human-readable clean message from mixed JSON/string payload.
     */
    private static function extractCleanMessage(string $raw): string
    {
        $raw = trim($raw);

        // Pattern: SPK 12345678 failed: {"status":false,"message":"..."}
        if (preg_match('/failed:\s*(\{.*\})/s', $raw, $m)) {
            $json = json_decode($m[1], true);
            if (! empty($json['message'])) {
                return (string) $json['message'];
            }
        }

        // Pattern: {"Message":"..."}
        if (str_starts_with($raw, '{') && str_ends_with($raw, '}')) {
            $json = json_decode($raw, true);
            if (! empty($json['message'])) {
                return (string) $json['message'];
            }
            if (! empty($json['Message'])) {
                return (string) $json['Message'];
            }
            if (! empty($json['ExceptionMessage'])) {
                return (string) $json['ExceptionMessage'];
            }
        }

        // Strip prefix "SPK 123 failed: " or "SPK 123 exception: "
        $stripped = preg_replace('/^SPK\s+\w+\s+(failed|exception):\s*/i', '', $raw);

        return trim($stripped ?? $raw);
    }

    /**
     * Extract BOM line number if present.
     */
    private static function extractBomLine(string $raw): ?string
    {
        if (preg_match("/(?:line|baris)[\s:#]+(\d+)/i", $raw, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Helper to construct uniform result array.
     */
    private static function buildResult(
        string $category,
        string $badgeLabel,
        string $badgeBg,
        string $badgeColor,
        string $badgeBorder,
        string $icon,
        string $title,
        string $userMessage,
        string $actionRecommendation,
        string $rawMessage,
        ?int $statusCode = null,
        ?string $spkCode = null,
        ?string $errorCode = null,
        ?string $bomLine = null
    ): array {
        return [
            'category' => $category,
            'badge_label' => $badgeLabel,
            'badge_bg' => $badgeBg,
            'badge_color' => $badgeColor,
            'badge_border' => $badgeBorder,
            'icon' => $icon,
            'title' => $title,
            'user_message' => $userMessage,
            'action_recommendation' => $actionRecommendation,
            'raw_message' => $rawMessage,
            'status_code' => $statusCode,
            'spk_code' => $spkCode,
            'error_code' => $errorCode ?: 'ERROR',
            'bom_line' => $bomLine,
        ];
    }
}
