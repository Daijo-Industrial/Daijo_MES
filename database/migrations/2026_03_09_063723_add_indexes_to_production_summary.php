<?php

// ============================================
// MIGRATION: Tambah INDEX untuk optimize query
// ============================================
// Jalankan: php artisan make:migration add_indexes_to_production_summary

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_summary', function (Blueprint $table) {
            // INDEX untuk WHERE clause
            $table->index('warehouse');
            $table->index('created_date');
            $table->index('sap_sent');

            // COMPOSITE INDEX untuk kombinasi filter (paling penting!)
            $table->index(['warehouse', 'created_date', 'sap_sent']);
            $table->index(['warehouse', 'created_date']);

            // INDEX untuk JOIN
            $table->index('spk_code');
        });

        Schema::table('production_scanned_data', function (Blueprint $table) {
            // INDEX untuk JOIN
            $table->index('spk_code');
            $table->index('item_code');
        });
    }

    public function down(): void
    {
        Schema::table('production_summary', function (Blueprint $table) {
            $table->dropIndex(['warehouse']);
            $table->dropIndex(['created_date']);
            $table->dropIndex(['sap_sent']);
            $table->dropIndex(['warehouse', 'created_date', 'sap_sent']);
            $table->dropIndex(['warehouse', 'created_date']);
            $table->dropIndex(['spk_code']);
        });

        Schema::table('production_scanned_data', function (Blueprint $table) {
            $table->dropIndex(['spk_code']);
            $table->dropIndex(['item_code']);
        });
    }
};
