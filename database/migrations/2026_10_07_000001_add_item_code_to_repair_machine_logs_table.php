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
        if (Schema::hasTable('repair_machine_logs') && !Schema::hasColumn('repair_machine_logs', 'item_code')) {
            Schema::table('repair_machine_logs', function (Blueprint $table) {
                $table->string('item_code')->nullable()->after('pic');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('repair_machine_logs') && Schema::hasColumn('repair_machine_logs', 'item_code')) {
            Schema::table('repair_machine_logs', function (Blueprint $table) {
                $table->dropColumn('item_code');
            });
        }
    }
};
