<?php

namespace App\Console\Commands;

use App\Models\ScannedData;
use App\Models\WmsPalletForm;
use App\Models\WmsPalletFormDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMismatchedPalletDetails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wms:fix-pallet-details {--dry-run : Only report without restoring}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore WmsPalletFormDetail records that were falsely soft-deleted without matching SO scanned_data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Scanning for falsely soft-deleted WmsPalletFormDetail records...');

        $trashedDetails = WmsPalletFormDetail::onlyTrashed()->get();

        if ($trashedDetails->isEmpty()) {
            $this->info('No soft-deleted pallet details found.');

            return 0;
        }

        $restoredCount = 0;
        $affectedPalletIds = [];

        DB::beginTransaction();
        try {
            foreach ($trashedDetails as $detail) {
                // Check if there is an actual matching scanned_data record for this specific part, SPK & label, created after the pallet detail was created
                $hasScannedData = ScannedData::where('item_code', $detail->part_no)
                    ->where('label', $detail->label)
                    ->when(! empty($detail->spk_no), function ($q) use ($detail) {
                        $q->where('spk_code', $detail->spk_no);
                    }, function ($q) {
                        $q->where(function ($sub) {
                            $sub->whereNull('spk_code')->orWhere('spk_code', '');
                        });
                    })
                    ->when($detail->created_at, function ($q) use ($detail) {
                        // Scan must have occurred on or after the box/pallet creation
                        $q->where('created_at', '>=', $detail->created_at->copy()->subDays(1));
                    })
                    ->exists();

                if (! $hasScannedData) {
                    $this->line("Found falsely deleted: Detail ID #{$detail->id}, Pallet: {$detail->pallet_form_id}, Part: {$detail->part_no}, SPK: {$detail->spk_no}, Label: {$detail->label}, Qty: {$detail->qty}");

                    if (! $dryRun) {
                        $detail->restore();
                    }

                    $restoredCount++;
                    $affectedPalletIds[] = $detail->pallet_form_id;
                }
            }

            $affectedPalletIds = array_unique($affectedPalletIds);

            if (! $dryRun && ! empty($affectedPalletIds)) {
                foreach ($affectedPalletIds as $palletId) {
                    $pallet = WmsPalletForm::withTrashed()->where('pallet_id', $palletId)->first();
                    if ($pallet) {
                        $activeDetails = WmsPalletFormDetail::where('pallet_form_id', $palletId)->get();
                        $activeBoxes = $activeDetails->count();
                        $activeQty = (float) $activeDetails->sum('qty');

                        if ($activeQty > 0) {
                            $pallet->box_qty = $activeBoxes;
                            $pallet->total_pallet_qty = $activeQty;
                            if ($pallet->status === 'OUT') {
                                $pallet->status = 'STORED';
                            }
                            if ($pallet->trashed()) {
                                $pallet->restore();
                            }
                            $pallet->save();
                            $this->info("Updated Pallet {$palletId}: Box Qty = {$activeBoxes}, Total Qty = {$activeQty}, Status = {$pallet->status}");
                        }
                    }
                }
            }

            DB::commit();

            if ($dryRun) {
                $this->warn("[DRY RUN] {$restoredCount} details would be restored across " . count($affectedPalletIds) . ' pallets.');
            } else {
                $this->info("Successfully restored {$restoredCount} details across " . count($affectedPalletIds) . ' pallets.');
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to fix pallet details: ' . $e->getMessage());

            return 1;
        }
    }
}
