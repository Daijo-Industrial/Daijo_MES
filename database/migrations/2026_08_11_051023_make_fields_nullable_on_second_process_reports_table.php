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
        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->string('model')->nullable()->change();
            $table->string('part_name')->nullable()->change();
            $table->string('customer')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->string('model')->nullable(false)->change();
            $table->string('part_name')->nullable(false)->change();
            $table->string('customer')->nullable(false)->change();
        });
    }
};
