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
        Schema::table('production_store_handover_logs', function (Blueprint $table) {
            $table->string('pallet_id', 100)->nullable()->index()->after('handover_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_store_handover_logs', function (Blueprint $table) {
            $table->dropColumn('pallet_id');
        });
    }
};
