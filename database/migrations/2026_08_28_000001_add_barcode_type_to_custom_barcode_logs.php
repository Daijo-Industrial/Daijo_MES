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
        Schema::table('custom_barcode_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_barcode_logs', 'barcode_type')) {
                $table->string('barcode_type', 50)->default('default')->after('customer');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_barcode_logs', function (Blueprint $table) {
            if (Schema::hasColumn('custom_barcode_logs', 'barcode_type')) {
                $table->dropColumn('barcode_type');
            }
        });
    }
};
