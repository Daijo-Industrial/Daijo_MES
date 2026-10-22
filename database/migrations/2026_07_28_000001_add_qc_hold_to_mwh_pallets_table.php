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
        Schema::table('mwh_pallets', function (Blueprint $table) {
            $table->boolean('is_qc_hold')->default(false)->after('status');
            $table->text('qc_hold_reason')->nullable()->after('is_qc_hold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mwh_pallets', function (Blueprint $table) {
            $table->dropColumn(['is_qc_hold', 'qc_hold_reason']);
        });
    }
};
