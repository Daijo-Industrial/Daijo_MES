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
            if (! Schema::hasColumn('master_list_items', 'project_code')) {
                $table->string('project_code')->nullable()->after('cycle_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_list_items', function (Blueprint $table) {
            if (Schema::hasColumn('master_list_items', 'project_code')) {
                $table->dropColumn('project_code');
            }
        });
    }
};
