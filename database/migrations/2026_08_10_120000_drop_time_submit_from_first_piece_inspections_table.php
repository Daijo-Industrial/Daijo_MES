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
        Schema::table('first_piece_inspections', function (Blueprint $table) {
            if (Schema::hasColumn('first_piece_inspections', 'time_submit')) {
                $table->dropColumn('time_submit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('first_piece_inspections', function (Blueprint $table) {
            $table->string('time_submit')->nullable();
        });
    }
};
