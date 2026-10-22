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
        if (!Schema::hasColumn('wms_pallet_forms', 'assigned_at')) {
            Schema::table('wms_pallet_forms', function (Blueprint $table) {
                $table->timestamp('assigned_at')->nullable()->after('position_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('wms_pallet_forms', 'assigned_at')) {
            Schema::table('wms_pallet_forms', function (Blueprint $table) {
                $table->dropColumn('assigned_at');
            });
        }
    }
};
