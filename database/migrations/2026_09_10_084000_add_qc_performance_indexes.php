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
        Schema::table('production_summary', function (Blueprint $table) {
            // Index for individual qc_status column
            if (!Schema::hasIndex('production_summary', 'production_summary_qc_status_index')) {
                $table->index('qc_status');
            }

            // Composite index to optimize the exact QC filter query:
            // WHERE sap_sent = 1 AND warehouse = 'KRFFI' AND qc_status IN (0,2) ORDER BY created_date DESC, id DESC
            $table->index(['warehouse', 'sap_sent', 'qc_status', 'created_date'], 'ps_wh_sap_qc_date_idx');
        });

        Schema::table('qc_transfer_logs', function (Blueprint $table) {
            // Composite index for fast lookup of scanned_data_id within a production_summary_id
            $table->index(['production_summary_id', 'scanned_data_id'], 'qctl_summary_scanned_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_summary', function (Blueprint $table) {
            $table->dropIndex('production_summary_qc_status_index');
            $table->dropIndex('ps_wh_sap_qc_date_idx');
        });

        Schema::table('qc_transfer_logs', function (Blueprint $table) {
            $table->dropIndex('qctl_summary_scanned_idx');
        });
    }
};
