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
        if (Schema::hasTable('inspection_reports') && ! Schema::hasColumn('inspection_reports', 'status')) {
            Schema::table('inspection_reports', function (Blueprint $table) {
                $table->string('status', 30)
                    ->default('draft')
                    ->index()
                    ->after('inspector');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inspection_reports') && Schema::hasColumn('inspection_reports', 'status')) {
            Schema::table('inspection_reports', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            });
        }
    }
};
