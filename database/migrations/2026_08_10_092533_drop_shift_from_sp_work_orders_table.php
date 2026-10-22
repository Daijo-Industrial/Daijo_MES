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
        Schema::table('sp_work_orders', function (Blueprint $table) {
            $table->dropColumn('shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sp_work_orders', function (Blueprint $table) {
            $table->string('shift')->nullable()->after('unit_line');
        });
    }
};
