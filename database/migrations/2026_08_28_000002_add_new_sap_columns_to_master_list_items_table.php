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
            if (!Schema::hasColumn('master_list_items', 'family')) {
                $table->string('family')->nullable()->default('0')->after('cycle_time');
            }
            if (!Schema::hasColumn('master_list_items', 'description_in_foreign_lang')) {
                $table->string('description_in_foreign_lang')->nullable()->default('0')->after('family');
            }
            if (!Schema::hasColumn('master_list_items', 'color')) {
                $table->string('color')->nullable()->default('0')->after('description_in_foreign_lang');
            }
            if (!Schema::hasColumn('master_list_items', 'half_code_1')) {
                $table->string('half_code_1')->nullable()->default('0')->after('color');
            }
            if (!Schema::hasColumn('master_list_items', 'half_code_2')) {
                $table->string('half_code_2')->nullable()->default('0')->after('half_code_1');
            }
            if (!Schema::hasColumn('master_list_items', 'position')) {
                $table->string('position')->nullable()->default('0')->after('half_code_2');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_list_items', function (Blueprint $table) {
            $columnsToDrop = [];
            $checkColumns = ['family', 'description_in_foreign_lang', 'color', 'half_code_1', 'half_code_2', 'position'];

            foreach ($checkColumns as $col) {
                if (Schema::hasColumn('master_list_items', $col)) {
                    $columnsToDrop[] = $col;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
