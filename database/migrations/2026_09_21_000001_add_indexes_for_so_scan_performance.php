<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function addIndexIfMissing(string $table, string|array $columns, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $indexName) {
                $tableBlueprint->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            // Index already exists or table issue, ignore
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
                $tableBlueprint->dropIndex($indexName);
            });
        } catch (\Throwable $e) {
            // Index does not exist, ignore
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Indexes on scanned_data for fast SO scan lookups
        $this->addIndexIfMissing('scanned_data', ['doc_num', 'item_code'], 'idx_scanned_data_doc_item');
        $this->addIndexIfMissing('scanned_data', ['doc_num', 'spk_code', 'label'], 'idx_scanned_data_doc_spk_label');
        $this->addIndexIfMissing('scanned_data', ['spk_code', 'label'], 'idx_scanned_data_spk_label');
        $this->addIndexIfMissing('scanned_data', ['doc_num', 'label'], 'idx_scanned_data_doc_label');

        // 2. Indexes on so_datas for fast DO/SO item lookups
        $this->addIndexIfMissing('so_datas', ['doc_num', 'item_code'], 'idx_so_datas_doc_item');
        $this->addIndexIfMissing('so_datas', 'doc_num', 'idx_so_datas_doc_num');

        // 3. Indexes on wms_pallet_form_details for fast outbound pallet deduction
        $this->addIndexIfMissing('wms_pallet_form_details', ['part_no', 'label'], 'idx_wms_pfd_part_label');
        $this->addIndexIfMissing('wms_pallet_form_details', ['spk_no', 'label'], 'idx_wms_pfd_spk_label');

        // 4. Index on barcode_packaging_master for fast SO packaging lookup
        $this->addIndexIfMissing('barcode_packaging_master', 'so_number', 'idx_bpm_so_number');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('scanned_data', 'idx_scanned_data_doc_item');
        $this->dropIndexIfExists('scanned_data', 'idx_scanned_data_doc_spk_label');
        $this->dropIndexIfExists('scanned_data', 'idx_scanned_data_spk_label');
        $this->dropIndexIfExists('scanned_data', 'idx_scanned_data_doc_label');

        $this->dropIndexIfExists('so_datas', 'idx_so_datas_doc_item');
        $this->dropIndexIfExists('so_datas', 'idx_so_datas_doc_num');

        $this->dropIndexIfExists('wms_pallet_form_details', 'idx_wms_pfd_part_label');
        $this->dropIndexIfExists('wms_pallet_form_details', 'idx_wms_pfd_spk_label');

        $this->dropIndexIfExists('barcode_packaging_master', 'idx_bpm_so_number');
    }
};
