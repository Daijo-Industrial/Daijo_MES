<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create a temporary non-unique index to speed up duplicate searching and deletion
        Schema::table('production_scanned_data', function (Blueprint $table) {
            $table->index(['spk_code', 'label'], 'temp_spk_label_index');
        });

        // 2. Efficiently delete duplicates in a single JOIN query using the index (keeps the earliest scan)
        DB::delete("
            DELETE p1 FROM production_scanned_data p1
            INNER JOIN production_scanned_data p2 
                ON p1.spk_code = p2.spk_code 
                AND p1.label = p2.label 
                AND p1.id > p2.id
        ");

        // 3. Drop temporary index and apply the unique constraint
        Schema::table('production_scanned_data', function (Blueprint $table) {
            $table->dropIndex('temp_spk_label_index');
            $table->unique(['spk_code', 'label'], 'psd_spk_label_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_scanned_data', function (Blueprint $table) {
            $table->dropUnique('psd_spk_label_unique');
        });
    }
};
