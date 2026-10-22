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
        if (!Schema::hasColumn('daily_item_codes', 'material_lot')) {
            Schema::table('daily_item_codes', function (Blueprint $table) {
                $table->string('material_lot')->nullable()->after('remark');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('daily_item_codes', 'material_lot')) {
            Schema::table('daily_item_codes', function (Blueprint $table) {
                $table->dropColumn('material_lot');
            });
        }
    }
};
