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
        $positions = \App\Models\MwhPosition::all();
        $mwhService = app(\App\Services\MaterialWarehouseService::class);

        foreach ($positions as $pos) {
            $mwhService->updatePositionStatus($pos->id);
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
