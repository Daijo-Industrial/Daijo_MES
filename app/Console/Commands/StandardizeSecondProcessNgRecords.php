<?php

namespace App\Console\Commands;

use App\Models\SecondProcessNgRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class StandardizeSecondProcessNgRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'second-process:standardize-ng-records
                            {--dry-run : Preview changes without modifying the database}
                            {--force : Execute updates directly without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Standardize legacy Second Process NG records (convert NG-PROCESS/NG-PROCES variants to NG-PROSES, normalize ng_input_item, and preserve legacy notes in remark)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isForce = (bool) $this->option('force');

        $this->info($isDryRun
            ? '🔍 [DRY-RUN] Scanning second_process_ng_records for non-standard records...'
            : '🚀 Scanning second_process_ng_records for non-standard records...'
        );

        $query = SecondProcessNgRecord::query()
            ->where(function ($q) {
                $q->whereIn('ng_category', ['NG-PROCESS', 'NG-PROCES', 'PROCESS', 'PROCES'])
                  ->orWhere('ng_input_item', 'like', '%PROCESS%')
                  ->orWhere('ng_input_item', 'like', '%PROCES%')
                  ->orWhere(function ($sub) {
                      $sub->whereNull('ng_category')
                          ->whereNotNull('ng_input_item')
                          ->where('ng_input_item', '!=', '');
                  })
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('ng_input_item')
                          ->where('ng_input_item', '!=', '')
                          ->where('ng_input_item', 'not like', '%NG-PROSES%')
                          ->where('ng_input_item', 'not like', '%NG-INPUT%');
                  });
            });

        $totalCandidates = $query->count();

        if ($totalCandidates === 0) {
            $this->info('✅ All second_process_ng_records are already standardized. No changes needed.');
            return 0;
        }

        $this->comment("Found {$totalCandidates} potential candidate records to evaluate.");

        $recordsToUpdate = [];
        $stats = [
            'category_corrected' => 0,
            'category_backfilled' => 0,
            'input_item_normalized' => 0,
            'legacy_remarks_preserved' => 0,
        ];

        // Process candidate records
        $bar = $this->output->createProgressBar($totalCandidates);
        $bar->start();

        $query->chunkById(500, function ($records) use (&$recordsToUpdate, &$stats, $bar) {
            foreach ($records as $record) {
                $origCategory = $record->ng_category;
                $origInputItem = $record->ng_input_item;
                $origInputQty = $record->ng_input_qty;
                $origRemark = $record->remark;

                $newCategory = $origCategory;
                $newInputItem = $origInputItem;
                $newInputQty = $origInputQty;
                $newRemark = $origRemark;

                $catChanged = false;
                $catBackfilled = false;
                $inputChanged = false;
                $remarkChanged = false;

                // 1. Normalize ng_category typos
                if ($origCategory !== null) {
                    $upperCat = strtoupper(trim($origCategory));
                    if (in_array($upperCat, ['NG-PROCESS', 'NG-PROCES', 'PROCESS', 'PROCES'], true)) {
                        $newCategory = 'NG-PROSES';
                        $catChanged = true;
                    }
                }

                // 2. Normalize ng_input_item text for typos
                if (! empty($origInputItem)) {
                    $normalizedInput = preg_replace('/\bNG-PROCESS\b/i', 'NG-PROSES', $origInputItem);
                    $normalizedInput = preg_replace('/\bNG-PROCES\b/i', 'NG-PROSES', $normalizedInput);
                    if ($normalizedInput !== $origInputItem) {
                        $newInputItem = $normalizedInput;
                        $inputChanged = true;
                    }
                }

                // 3. Backfill ng_category if standard preset exists
                if ($newCategory === null && ! empty($newInputItem)) {
                    if (preg_match('/\bNG-PROSES\b|\bNG-PROCESS\b|\bNG-PROCES\b/i', $newInputItem)) {
                        $newCategory = 'NG-PROSES';
                        $catBackfilled = true;
                    } elseif (preg_match('/\bNG-INPUT\b/i', $newInputItem)) {
                        $newCategory = 'NG-INPUT';
                        $catBackfilled = true;
                    }
                }

                // 4. Handle legacy non-standard ng_input_item (notes, WIP markers, defect names in input item)
                $isNonStandardInput = ! empty($newInputItem) &&
                    ! preg_match('/\bNG-PROSES\b|\bNG-INPUT\b/i', $newInputItem);

                if ($isNonStandardInput) {
                    // Copy raw operator text to remark if remark is empty
                    if (empty($origRemark)) {
                        $newRemark = $origInputItem;
                        $remarkChanged = true;
                    }

                    // Determine appropriate category
                    if ($newCategory === null) {
                        if (preg_match('/\bWIP\b|\bPART-WIP\b/i', $newInputItem)) {
                            $newCategory = 'NG-INPUT';
                        } else {
                            $newCategory = 'NG-PROSES';
                        }
                        $catBackfilled = true;
                    }

                    // Standardize input item and quantity
                    $totalNg = (int) $record->total_ng;
                    if ($totalNg > 0) {
                        $newInputItem = "[{$totalNg}] {$newCategory}";
                        $newInputQty = $totalNg;
                        $inputChanged = true;
                    } else {
                        // total_ng is 0 (note preserved in remark)
                        $newInputItem = null;
                        $newInputQty = null;
                        $inputChanged = true;
                    }
                }

                if ($catChanged || $catBackfilled || $inputChanged || $remarkChanged) {
                    if ($catChanged) {
                        $stats['category_corrected']++;
                    }
                    if ($catBackfilled) {
                        $stats['category_backfilled']++;
                    }
                    if ($inputChanged) {
                        $stats['input_item_normalized']++;
                    }
                    if ($remarkChanged) {
                        $stats['legacy_remarks_preserved']++;
                    }

                    $recordsToUpdate[] = [
                        'id' => $record->id,
                        'ng_category' => $newCategory,
                        'ng_input_item' => $newInputItem,
                        'ng_input_qty' => $newInputQty,
                        'remark' => $newRemark,
                    ];
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $totalAffected = count($recordsToUpdate);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Candidates Evaluated', $totalCandidates],
                ['Records Requiring Updates', $totalAffected],
                ['Categories Typo Corrected (NG-PROCESS/PROCES -> NG-PROSES)', $stats['category_corrected']],
                ['Categories Backfilled from NULL', $stats['category_backfilled']],
                ['Input Items Normalized to Preset Format', $stats['input_item_normalized']],
                ['Legacy Operator Notes Preserved to Remark', $stats['legacy_remarks_preserved']],
            ]
        );

        if ($totalAffected === 0) {
            $this->info('✅ No records require modification.');
            return 0;
        }

        if ($isDryRun) {
            $this->info('🔍 [DRY-RUN] No changes were written to the database.');
            $this->info('Run without --dry-run to apply these changes.');
            return 0;
        }

        if (! $isForce && ! $this->confirm("Are you sure you want to update {$totalAffected} records in the database?", false)) {
            $this->warn('❌ Operation aborted by user. Database was not modified.');
            return 1;
        }

        $this->info("Applying updates to {$totalAffected} records within an atomic database transaction...");

        DB::beginTransaction();
        try {
            foreach (array_chunk($recordsToUpdate, 500) as $chunk) {
                foreach ($chunk as $item) {
                    SecondProcessNgRecord::where('id', $item['id'])->update([
                        'ng_category' => $item['ng_category'],
                        'ng_input_item' => $item['ng_input_item'],
                        'ng_input_qty' => $item['ng_input_qty'],
                        'remark' => $item['remark'],
                    ]);
                }
            }

            DB::commit();
            $this->info("🎉 Successfully updated {$totalAffected} records in second_process_ng_records!");
            return 0;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error("💥 An error occurred during database update: {$e->getMessage()}");
            $this->error('Transaction has been rolled back. Database remains untouched.');
            return 1;
        }
    }
}
