<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wms_racks')) {
            Schema::table('wms_racks', function (Blueprint $table) {
                if (!Schema::hasColumn('wms_racks', 'zone')) {
                    $table->string('zone', 50)->nullable()->default('DEFAULT')->after('orientation');
                }
                if (!Schema::hasColumn('wms_racks', 'sort_order')) {
                    $table->integer('sort_order')->default(0)->after('zone');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wms_racks')) {
            Schema::table('wms_racks', function (Blueprint $table) {
                if (Schema::hasColumn('wms_racks', 'zone')) {
                    $table->dropColumn('zone');
                }
                if (Schema::hasColumn('wms_racks', 'sort_order')) {
                    $table->dropColumn('sort_order');
                }
            });
        }
    }
};
