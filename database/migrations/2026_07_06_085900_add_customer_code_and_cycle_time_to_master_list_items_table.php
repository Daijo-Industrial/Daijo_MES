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
            if (! Schema::hasColumn('master_list_items', 'customer_code')) {
                $table->string('customer_code')->nullable()->after('cavity');
            }
            if (! Schema::hasColumn('master_list_items', 'cycle_time')) {
                $table->integer('cycle_time')->nullable()->after('customer_code');
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
            if (Schema::hasColumn('master_list_items', 'customer_code')) {
                $columnsToDrop[] = 'customer_code';
            }
            if (Schema::hasColumn('master_list_items', 'cycle_time')) {
                $columnsToDrop[] = 'cycle_time';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
