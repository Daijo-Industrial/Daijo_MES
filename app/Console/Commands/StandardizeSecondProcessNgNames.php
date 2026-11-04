<?php

namespace App\Console\Commands;

use App\Models\SecondProcessDefectAlias;
use App\Models\SecondProcessDefectCatalog;
use App\Models\SecondProcessNgRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class StandardizeSecondProcessNgNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'second-process:standardize-ng-names
                            {--dry-run : Preview changes without modifying the database}
                            {--force : Execute updates directly without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Standardize legacy Second Process NG names (ng_name) against canonical catalog and aliases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        $this->info($isDryRun
            ? '🔍 [DRY-RUN] Scanning second_process_ng_records for non-standard defect names...'
            : '🚀 Scanning second_process_ng_records for non-standard defect names...'
        );

        // 1. Build lookup dictionary: alias -> canonical name
        $aliases = SecondProcessDefectAlias::with('catalog')->get();
        $aliasMap = [];
        foreach ($aliases as $item) {
            if ($item->catalog) {
                $aliasMap[strtoupper(trim($item->alias))] = $item->catalog->name;
            }
        }

        // Canonical catalogs list
        $canonicalNames = SecondProcessDefectCatalog::pluck('name')->map(fn ($n) => strtoupper(trim($n)))->toArray();
        $canonicalLookup = array_flip($canonicalNames);

        // 2. Identify all distinct ng_name currently in database
        $distinctNames = SecondProcessNgRecord::selectRaw('ng_name, COUNT(*) as cnt')
            ->groupBy('ng_name')
            ->pluck('cnt', 'ng_name')
            ->toArray();

        $replacements = []; // raw_name => ['canonical' => ..., 'count' => ...]
        $totalRecordsToUpdate = 0;

        foreach ($distinctNames as $rawName => $count) {
            $trimmed = trim((string) $rawName);
            $upper = strtoupper($trimmed);

            // Determine if rawName requires standardization
            $targetCanonical = null;

            if (isset($aliasMap[$upper])) {
                $targetCanonical = $aliasMap[$upper];
            } elseif (isset($canonicalLookup[$upper]) && $rawName !== $upper) {
                // Same canonical name but has casing or whitespace discrepancies
                $targetCanonical = $upper;
            }

            if ($targetCanonical !== null && $rawName !== $targetCanonical) {
                $replacements[$rawName] = [
                    'canonical' => $targetCanonical,
                    'count' => $count,
                ];
                $totalRecordsToUpdate += $count;
            }
        }

        if (empty($replacements)) {
            $this->info('✅ All second_process_ng_records are already standardized. No alias replacements needed.');
            return 0;
        }

        // 3. Display summary of planned replacements
        $tableData = [];
        foreach ($replacements as $rawName => $info) {
            $tableData[] = [
                $rawName,
                '➔',
                $info['canonical'],
                number_format($info['count']),
            ];
        }

        $this->table(
            ['Original Name (In Database)', '', 'Target Canonical Defect', 'Record Count'],
            $tableData
        );

        $this->newLine();
        $this->info("Total distinct alias variants: " . count($replacements));
        $this->info("Total records requiring standardization: " . number_format($totalRecordsToUpdate));
        $this->newLine();

        if ($isDryRun) {
            $this->info('🔍 [DRY-RUN] No changes were written to the database.');
            $this->info('Run without --dry-run to apply these changes.');
            return 0;
        }

        if (! $isForce && ! $this->confirm("Are you sure you want to update {$totalRecordsToUpdate} records in the database?", false)) {
            $this->warn('❌ Operation aborted by user. Database was not modified.');
            return 1;
        }

        $this->info("Applying updates across {$totalRecordsToUpdate} records within an atomic database transaction...");

        DB::beginTransaction();
        try {
            $bar = $this->output->createProgressBar(count($replacements));
            $bar->start();

            foreach ($replacements as $rawName => $info) {
                SecondProcessNgRecord::where('ng_name', $rawName)
                    ->update(['ng_name' => $info['canonical']]);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            DB::commit();
            $this->info("🎉 Successfully standardized {$totalRecordsToUpdate} records in second_process_ng_records!");
            return 0;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error("💥 An error occurred during database update: {$e->getMessage()}");
            $this->error('Transaction has been rolled back. Database remains untouched.');
            return 1;
        }
    }
}
