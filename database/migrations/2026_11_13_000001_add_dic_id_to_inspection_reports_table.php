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
        if (Schema::hasTable('inspection_reports') && ! Schema::hasColumn('inspection_reports', 'dic_id')) {
            Schema::table('inspection_reports', function (Blueprint $table) {
                $table->foreignId('dic_id')
                    ->nullable()
                    ->after('document_number')
                    ->constrained('daily_item_codes')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inspection_reports') && Schema::hasColumn('inspection_reports', 'dic_id')) {
            Schema::table('inspection_reports', function (Blueprint $table) {
                $table->dropForeign(['dic_id']);
                $table->dropColumn('dic_id');
            });
        }
    }
};

