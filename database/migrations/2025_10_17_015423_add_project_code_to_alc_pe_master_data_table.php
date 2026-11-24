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
        Schema::table('alc_pe_master_data', function (Blueprint $table) {
            $table->string('project_code')->nullable()->after('part_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alc_pe_master_data', function (Blueprint $table) {
            $table->dropColumn('project_code');
        });
    }
};
