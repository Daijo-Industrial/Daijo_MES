<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_warehouses', function (Blueprint $table) {
            $table->string('layout_image')->nullable()->after('whse_name');
            $table->string('exit_location')->default('BOTTOM_RIGHT')->after('layout_image');
        });
    }

    public function down(): void
    {
        Schema::table('wms_warehouses', function (Blueprint $table) {
            $table->dropColumn(['layout_image', 'exit_location']);
        });
    }
};
