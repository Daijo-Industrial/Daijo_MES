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
        Schema::table('sp_downtime_entries', function (Blueprint $table) {
            $table->string('category')->nullable()->after('reason');       // Man, Mesin, Part, PPS, Lingkungan
            $table->text('countermeasure')->nullable()->after('remarks');   // penanganan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sp_downtime_entries', function (Blueprint $table) {
            $table->dropColumn(['category', 'countermeasure']);
        });
    }
};
