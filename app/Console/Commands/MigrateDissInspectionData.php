<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MigrateDissInspectionData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mes:migrate-diss-inspection-data
                            {--diss-connection=diss_mysql : Database connection name for DISS}
                            {--chunk=500 : Chunk size for copying records}
                            {--truncate : Truncate target tables before migration}
                            {--dry-run : Simulate migration without writing data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate historical molding IPQC inspection data from DISS MySQL to Daijo_MES';

    /**
     * Inspection tables in topological insertion order.
     *
     * @var array<int, string>
     */
    protected array $tables = [
        'inspection_defects',
        'inspection_reports',
        'detail_inspection_reports',
        'first_inspections',
        'second_inspections',
        'inspection_dimensions',
        'inspection_problems',
        'inspection_packagings',
        'inspection_samplings',
        'inspection_quantities',
        'inspection_judgements',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dissConnection = (string) $this->option('diss-connection');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $truncate = (bool) $this->option('truncate');
        $dryRun = (bool) $this->option('dry-run');

        $this->info("=== Starting DISS -> Daijo_MES Inspection Data Migration ===");
        $this->line("Source Connection: <comment>{$dissConnection}</comment>");
        $this->line("Chunk Size:        <comment>{$chunkSize}</comment>");
        $this->line("Dry Run:           <comment>" . ($dryRun ? 'YES (No writes)' : 'NO (Live write)') . "</comment>");
        $this->line("Truncate:          <comment>" . ($truncate ? 'YES' : 'NO') . "</comment>");
        $this->info($dryRun ? '[DRY-RUN] Simulating data migration without writes.' : '[LIVE] Writing migrated data to tables.');

        // 1. Verify DISS connection
        try {
            DB::connection($dissConnection)->getPdo();
            $this->info("✓ Successfully connected to source database ({$dissConnection}).");
        } catch (Throwable $e) {
            $this->error("✗ Cannot connect to source database connection '{$dissConnection}': " . $e->getMessage());
            $this->warn("Please ensure DISS database configuration in config/database.php and .env (DISS_DB_*) are properly set.");
            return Command::FAILURE;
        }

        // 2. Truncate if requested
        if ($truncate) {
            if (! $this->confirm('Are you sure you want to TRUNCATE all 11 inspection tables in Daijo_MES before migrating?', false)) {
                $this->warn('Truncate canceled by user.');
                return Command::INVALID;
            }

            if (! $dryRun) {
                Schema::disableForeignKeyConstraints();
                foreach (array_reverse($this->tables) as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->truncate();
                        $this->line("  Truncated target table: <comment>{$table}</comment>");
                    }
                }
                Schema::enableForeignKeyConstraints();
                $this->info('✓ Target tables truncated successfully.');
            } else {
                $this->line('[DRY RUN] Skipped truncating target tables.');
            }
        }

        // 3. Migrate each table in dependency order
        $summary = [];

        foreach ($this->tables as $targetTable) {
            $sourceTable = $this->resolveSourceTable($dissConnection, $targetTable);

            if (! $sourceTable) {
                $this->warn("! Table '{$targetTable}' not found in DISS database. Skipping.");
                $summary[] = [
                    'table' => $targetTable,
                    'source_rows' => 0,
                    'migrated_rows' => 0,
                    'target_total' => Schema::hasTable($targetTable) ? DB::table($targetTable)->count() : 0,
                    'status' => 'SKIPPED (Source missing)',
                ];
                continue;
            }

            $sourceCount = DB::connection($dissConnection)->table($sourceTable)->count();
            $this->line("\nMigrating <info>{$targetTable}</info> (Source: {$sourceTable}, Total rows: {$sourceCount})...");

            if ($sourceCount === 0) {
                $this->line("  Source table is empty. Skipping.");
                $summary[] = [
                    'table' => $targetTable,
                    'source_rows' => 0,
                    'migrated_rows' => 0,
                    'target_total' => DB::table($targetTable)->count(),
                    'status' => 'SKIPPED (Empty)',
                ];
                continue;
            }

            $copied = 0;
            $bar = $this->output->createProgressBar($sourceCount);
            $bar->start();

            DB::connection($dissConnection)->table($sourceTable)
                ->orderBy('id')
                ->chunk($chunkSize, function ($rows) use ($targetTable, &$copied, $bar, $dryRun) {
                    $payload = $rows->map(function ($row) use ($targetTable) {
                        return $this->normalizeRowData((array) $row, $targetTable);
                    })->all();

                    if (! $dryRun && ! empty($payload)) {
                        $allKeys = array_keys($payload[0]);
                        $updateKeys = array_values(array_diff($allKeys, ['id']));
                        DB::table($targetTable)->upsert($payload, ['id'], $updateKeys);
                    }

                    $count = count($payload);
                    $copied += $count;
                    $bar->advance($count);
                });

            $bar->finish();
            $this->newLine();

            $targetCount = DB::table($targetTable)->count();
            $summary[] = [
                'table' => $targetTable,
                'source_rows' => $sourceCount,
                'migrated_rows' => $copied,
                'target_total' => $targetCount,
                'status' => $dryRun ? 'DRY-RUN' : 'COMPLETED',
            ];
        }

        // 4. Output Summary Table
        $this->newLine();
        $this->info("=== Migration Summary ===");
        $this->table(
            ['Table Name', 'Source Rows', 'Processed', 'Target Total', 'Status'],
            $summary
        );

        $this->info("✓ IPQC Inspection Reports migration process completed.");
        return Command::SUCCESS;
    }

    /**
     * Resolve actual source table name, accounting for legacy table renames in DISS.
     */
    protected function resolveSourceTable(string $connection, string $targetTable): ?string
    {
        if (Schema::connection($connection)->hasTable($targetTable)) {
            return $targetTable;
        }

        // Legacy name fallback in older DISS migrations
        if ($targetTable === 'inspection_dimensions' && Schema::connection($connection)->hasTable('inspection_measurements')) {
            return 'inspection_measurements';
        }

        return null;
    }

    /**
     * Normalize legacy row column names to target schema.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function normalizeRowData(array $data, string $targetTable): array
    {
        if ($targetTable === 'inspection_dimensions') {
            if (array_key_exists('part', $data) && ! array_key_exists('area', $data)) {
                $data['area'] = $data['part'];
                unset($data['part']);
            }
        }

        if ($targetTable === 'detail_inspection_reports') {
            if (array_key_exists('quarter', $data) && ! array_key_exists('period', $data)) {
                $data['period'] = $data['quarter'];
                unset($data['quarter']);
            }
        }

        if ($targetTable === 'inspection_problems') {
            if (array_key_exists('remark', $data) && ! array_key_exists('remarks', $data)) {
                $data['remarks'] = $data['remark'];
                unset($data['remark']);
            }
        }

        if ($targetTable === 'inspection_packagings') {
            if (array_key_exists('quantity', $data) && ! array_key_exists('snp', $data)) {
                $data['snp'] = $data['quantity'];
                unset($data['quantity']);
            }
        }

        return $data;
    }
}
