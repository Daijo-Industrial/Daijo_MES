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
        Schema::table('master_list_items', function (Blueprint $table) {
            if (!Schema::hasColumn('master_list_items', 'part_weight')) {
                $table->decimal('part_weight', 10, 3)->nullable()->default(0)->after('family');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_list_items', function (Blueprint $table) {
            if (Schema::hasColumn('master_list_items', 'part_weight')) {
                $table->dropColumn('part_weight');
            }
        });
    }
};
