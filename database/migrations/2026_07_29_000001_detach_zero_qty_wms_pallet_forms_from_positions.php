<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Get position IDs of all zero-qty or OUT WMS pallet forms
        $affectedPosIds = \App\Models\WmsPalletForm::whereNotNull('position_id')
            ->where(function($q) {
                $q->where('total_pallet_qty', '<=', 0)
                  ->orWhere('status', 'OUT');
            })
            ->pluck('position_id')
            ->filter()
            ->unique();

        // 2. Clear position_id to null for zero-qty or OUT pallets
        \App\Models\WmsPalletForm::whereNotNull('position_id')
            ->where(function($q) {
                $q->where('total_pallet_qty', '<=', 0)
                  ->orWhere('status', 'OUT');
            })
            ->update(['position_id' => null]);

        // 3. Recalculate rack position statuses for affected positions
        if ($affectedPosIds->isNotEmpty()) {
            $wmsService = app(\App\Services\WmsService::class);
            foreach ($affectedPosIds as $posId) {
                $wmsService->updatePositionStatus($posId);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op for cleanup migration
    }
};
