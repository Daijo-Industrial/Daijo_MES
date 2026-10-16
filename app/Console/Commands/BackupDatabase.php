<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--keep-days=14 : Number of days to keep backup files} {--path= : Custom backup directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis backup database MySQL ke file .sql dengan auto-pruning file lama';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses backup database...');

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');

        if (empty($database)) {
            $this->error('Nama database belum dikonfigurasi di .env!');

            return 1;
        }

        // 1. Tentukan direktori tujuan backup
        $customPath = $this->option('path');
        $backupDir = $customPath ?: storage_path('app/backups');

        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true, true);
        }

        // 2. Format nama file backup: backup_DBNAME_YYYY-MM-DD_HHmmss.sql
        $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d_His');
        $fileName = "backup_{$database}_{$timestamp}.sql";
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        // 3. Cari path mysqldump (kompatibel untuk Windows XAMPP & Linux/Docker)
        $mysqldumpPath = $this->findMysqldumpBinary();

        if (! $mysqldumpPath) {
            $this->error('Executable mysqldump tidak ditemukan di sistem atau folder XAMPP!');
            Log::error('Database backup failed: mysqldump binary not found.');

            return 1;
        }

        $this->line("Menggunakan mysqldump: {$mysqldumpPath}");

        // 4. Bangun command mysqldump
        // Khusus Windows & Linux, parameter password ditangani secara aman
        $hostArg = "--host={$host}";
        $portArg = "--port={$port}";
        $userArg = "--user={$username}";
        $passArg = ! empty($password) ? '--password=' . escapeshellarg($password) : '';
        $options = '--routines --triggers --single-transaction --quick --lock-tables=false';

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows command execution
            $cmd = "\"{$mysqldumpPath}\" {$hostArg} {$portArg} {$userArg} {$passArg} {$options} {$database} > \"{$filePath}\"";
        } else {
            // Linux / Docker execution
            $cmd = "{$mysqldumpPath} {$hostArg} {$portArg} {$userArg} {$passArg} {$options} {$database} > " . escapeshellarg($filePath);
        }

        $this->line("Menjalankan dump untuk database: {$database}...");

        $output = [];
        $returnVar = null;
        exec($cmd, $output, $returnVar);

        // Verifikasi apakah file berhasil dibuat dan tidak kosong
        if ($returnVar === 0 && File::exists($filePath) && File::size($filePath) > 0) {
            $fileSizeBytes = File::size($filePath);
            $formattedSize = $this->formatBytes($fileSizeBytes);

            $this->info('✅ Backup BERHASIL dibuat!');
            $this->line("📁 Lokasi File : {$filePath}");
            $this->line("📦 Ukuran File : {$formattedSize}");

            Log::info("Database backup created successfully: {$fileName} ({$formattedSize})");

            // 5. Bersihkan backup lama (Auto-pruning)
            $keepDays = (int) $this->option('keep-days');
            $this->pruneOldBackups($backupDir, $keepDays);

            return 0;
        } else {
            $this->error("❌ Gagal membuat backup database (Exit Code: {$returnVar})");
            Log::error("Database backup failed for {$database}. Exit code: {$returnVar}");

            // Hapus file rusak / 0 bytes jika ada
            if (File::exists($filePath) && File::size($filePath) === 0) {
                File::delete($filePath);
            }

            return 1;
        }
    }

    /**
     * Cari lokasi mysqldump di Windows XAMPP maupun Linux
     */
    private function findMysqldumpBinary(): ?string
    {
        // Daftar path umum di XAMPP Windows
        $commonWindowsPaths = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'D:\\xampp\\mysql\\bin\\mysqldump.exe',
            'E:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe',
            'C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe',
        ];

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            foreach ($commonWindowsPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Coba dari PATH environment
            $output = [];
            $code = 0;
            exec('where mysqldump', $output, $code);
            if ($code === 0 && ! empty($output[0])) {
                return trim($output[0]);
            }
        } else {
            // Linux / Unix / Docker
            $output = [];
            $code = 0;
            exec('which mysqldump', $output, $code);
            if ($code === 0 && ! empty($output[0])) {
                return trim($output[0]);
            }
            if (file_exists('/usr/bin/mysqldump')) {
                return '/usr/bin/mysqldump';
            }
            if (file_exists('/usr/local/bin/mysqldump')) {
                return '/usr/local/bin/mysqldump';
            }
        }

        return 'mysqldump';
    }

    /**
     * Hapus file backup yang lebih tua dari $keepDays
     */
    private function pruneOldBackups(string $backupDir, int $keepDays): void
    {
        if ($keepDays <= 0) {
            return;
        }

        $thresholdDate = Carbon::now('Asia/Jakarta')->subDays($keepDays);
        $files = File::files($backupDir);
        $deletedCount = 0;

        foreach ($files as $file) {
            if ($file->getExtension() === 'sql') {
                $fileModifiedTime = Carbon::createFromTimestamp($file->getMTime(), 'Asia/Jakarta');
                if ($fileModifiedTime->lessThan($thresholdDate)) {
                    File::delete($file->getRealPath());
                    $deletedCount++;
                }
            }
        }

        if ($deletedCount > 0) {
            $this->line("🧹 Membersihkan {$deletedCount} file backup lama (> {$keepDays} hari).");
            Log::info("Cleaned {$deletedCount} old database backup files older than {$keepDays} days.");
        }
    }

    /**
     * Format bytes to readable string (KB, MB, GB)
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
