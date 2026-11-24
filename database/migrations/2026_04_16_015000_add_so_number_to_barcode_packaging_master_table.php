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
        Schema::table('barcode_packaging_master', function (Blueprint $table) {
            $table->string('so_number')->nullable()->after('noDokumen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barcode_packaging_master', function (Blueprint $table) {
            $table->dropColumn('so_number');
        });
    }
};
