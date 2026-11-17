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
        Schema::table('wms_pallet_forms', function (Blueprint $table) {
            if (! Schema::hasColumn('wms_pallet_forms', 'is_split')) {
                $table->boolean('is_split')->default(false)->after('status');
            }
            if (! Schema::hasColumn('wms_pallet_forms', 'split_from_pallet_id')) {
                $table->string('split_from_pallet_id')->nullable()->after('is_split');
            }
            if (! Schema::hasColumn('wms_pallet_forms', 'split_into')) {
                $table->json('split_into')->nullable()->after('split_from_pallet_id');
            }
            if (! Schema::hasColumn('wms_pallet_forms', 'split_at')) {
                $table->timestamp('split_at')->nullable()->after('split_into');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_pallet_forms', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['split_at', 'split_into', 'split_from_pallet_id', 'is_split'] as $col) {
                if (Schema::hasColumn('wms_pallet_forms', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};

