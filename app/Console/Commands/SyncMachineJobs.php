<?php

namespace App\Console\Commands;

use App\Models\MachineJob;
use App\Models\User;
use Illuminate\Console\Command;

class SyncMachineJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'machine:sync-jobs 
                            {--role=4 : Role ID user yang dicek (default: 4)}
                            {--dry-run : Simulasi pengecekan tanpa menyimpan data}';

    /**
     * Alternative command names.
     *
     * @var array
     */
    protected $aliases = ['machine-jobs:sync', 'machine:ensure-jobs'];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek user dengan role_id 4 yang belum memiliki machine_jobs, lalu buatkan record machine_jobs-nya';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $roleId = (int) $this->option('role');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Memeriksa user dengan role_id = {$roleId} yang belum memiliki machine_jobs...");

        // Ambil ID user yang sudah terdaftar di machine_jobs
        $existingUserIds = MachineJob::pluck('user_id')->filter()->unique()->toArray();

        // Cari user dengan role_id yang belum ada di machine_jobs
        $users = User::where('role_id', $roleId)
            ->whereNotIn('id', $existingUserIds)
            ->orderBy('name')
            ->get();

        if ($users->isEmpty()) {
            $this->info("✅ Semua user dengan role_id {$roleId} sudah memiliki machine_jobs. Tidak ada data yang perlu dibuat.");
            return Command::SUCCESS;
        }

        $this->warn("Ditemukan {$users->count()} user (role_id {$roleId}) yang belum memiliki machine_jobs:");

        $tableData = $users->map(fn($u) => [
            'ID' => $u->id,
            'Name' => $u->name,
            'Email' => $u->email,
            'Branch ID' => $u->branch_id ?? '-',
        ])->toArray();

        $this->table(['ID', 'Name', 'Email', 'Branch ID'], $tableData);

        if ($isDryRun) {
            $this->comment('Mode --dry-run aktif. Tidak ada perubahan yang disimpan ke database.');
            return Command::SUCCESS;
        }

        $createdCount = 0;
        foreach ($users as $user) {
            MachineJob::firstOrCreate(
                ['user_id' => $user->id],
                ['branch_id' => $user->branch_id]
            );
            $createdCount++;
        }

        $this->info("🎉 Berhasil membuat {$createdCount} record machine_jobs baru!");

        return Command::SUCCESS;
    }
}
