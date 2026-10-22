<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_pallet_forms', function (Blueprint $table) {
            $table->decimal('sap_sync_duration', 8, 2)->nullable()->after('sap_sync_at');
        });
    }

    public function down(): void
    {
        Schema::table('wms_pallet_forms', function (Blueprint $table) {
            $table->dropColumn(['sap_sync_duration']);
        });
    }
};
