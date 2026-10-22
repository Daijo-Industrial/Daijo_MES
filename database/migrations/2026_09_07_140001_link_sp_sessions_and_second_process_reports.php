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
            $table->foreignId('second_process_report_id')
                ->nullable()
                ->after('status')
                ->constrained('second_process_reports')
                ->nullOnDelete();
        });

        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->foreignId('sp_production_session_id')
                ->nullable()
                ->after('id')
                ->constrained('sp_production_sessions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sp_production_session_id');
        });

        Schema::table('sp_production_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('second_process_report_id');
        });
    }
};
