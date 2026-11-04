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
        if (!Schema::hasColumn('wms_pallet_forms', 'plant')) {
            Schema::table('wms_pallet_forms', function (Blueprint $table) {
                $table->string('plant', 20)->default('KBN')->after('lot_no')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('wms_pallet_forms', 'plant')) {
            Schema::table('wms_pallet_forms', function (Blueprint $table) {
                $table->dropIndex(['plant']);
                $table->dropColumn('plant');
            });
        }
    }
};
