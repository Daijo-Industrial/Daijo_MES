<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename the old table to the new table
        Schema::rename('failed_machine_jobs', 'production_reports');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename the table back to the original name if rolled back
        Schema::rename('production_reports', 'failed_machine_jobs');
    }
};
