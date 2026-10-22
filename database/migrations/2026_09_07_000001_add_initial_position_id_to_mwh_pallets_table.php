<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('mwh_pallets')) {
            Schema::table('mwh_pallets', function (Blueprint $table) {
                if (!Schema::hasColumn('mwh_pallets', 'initial_position_id')) {
                    $table->foreignId('initial_position_id')
                        ->nullable()
                        ->after('position_id')
                        ->constrained('mwh_positions')
                        ->nullOnDelete();
                }
            });

            // 1. Backfill initial_position_id from existing active position_id
            DB::table('mwh_pallets')
                ->whereNotNull('position_id')
                ->whereNull('initial_position_id')
                ->update(['initial_position_id' => DB::raw('position_id')]);

            // 2. Backfill initial_position_id from historical outgoings for consumed/emptied pallets
            if (Schema::hasTable('mwh_outgoings')) {
                DB::statement("
                    UPDATE mwh_pallets p
                    INNER JOIN (
                        SELECT pallet_id, position_id 
                        FROM mwh_outgoings 
                        WHERE position_id IS NOT NULL 
                        GROUP BY pallet_id, position_id
                    ) o ON p.pallet_id = o.pallet_id
                    SET p.initial_position_id = o.position_id
                    WHERE p.initial_position_id IS NULL
                ");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mwh_pallets') && Schema::hasColumn('mwh_pallets', 'initial_position_id')) {
            Schema::table('mwh_pallets', function (Blueprint $table) {
                $table->dropForeign(['initial_position_id']);
                $table->dropColumn('initial_position_id');
            });
        }
    }
};
