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
            if (! Schema::hasColumn('wms_pallet_forms', 'merged_from')) {
                $table->json('merged_from')->nullable()->after('split_into');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wms_pallet_forms', function (Blueprint $table) {
            if (Schema::hasColumn('wms_pallet_forms', 'merged_from')) {
                $table->dropColumn('merged_from');
            }
        });
    }
};
