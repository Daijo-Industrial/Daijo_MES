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
        Schema::table('sp_production_sessions', function (Blueprint $table) {
            $table->text('production_notes')->nullable()->after('remarks');
            $table->text('ng_remarks')->nullable()->after('production_notes');
            $table->string('absent_employees')->nullable()->after('ng_remarks');
            $table->json('next_production_schedule')->nullable()->after('absent_employees');
            $table->string('output_destination')->nullable()->after('next_production_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sp_production_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'production_notes',
                'ng_remarks',
                'absent_employees',
                'next_production_schedule',
                'output_destination',
            ]);
        });
    }
};
