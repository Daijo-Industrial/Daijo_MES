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
        if (!Schema::hasColumn('sp_production_sessions', 'paused_at')) {
            Schema::table('sp_production_sessions', function (Blueprint $table) {
                $table->timestamp('paused_at')->nullable()->after('started_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('sp_production_sessions', 'paused_at')) {
            Schema::table('sp_production_sessions', function (Blueprint $table) {
                $table->dropColumn('paused_at');
            });
        }
    }
};
