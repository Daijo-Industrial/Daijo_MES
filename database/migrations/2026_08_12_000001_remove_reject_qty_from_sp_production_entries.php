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
        if (Schema::hasColumn('sp_production_entries', 'reject_qty')) {
            Schema::table('sp_production_entries', function (Blueprint $table) {
                $table->dropColumn('reject_qty');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('sp_production_entries', 'reject_qty')) {
            Schema::table('sp_production_entries', function (Blueprint $table) {
                $table->integer('reject_qty')->default(0)->after('good_qty');
            });
        }
    }
};
