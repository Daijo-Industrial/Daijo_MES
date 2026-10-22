<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('files', function (Blueprint $table) {
                $table->renameColumn('machine_name', 'item_code');
            });
        } else {
            DB::statement('ALTER TABLE `files` CHANGE `machine_name` `item_code` VARCHAR(255) null');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('files', function (Blueprint $table) {
                $table->renameColumn('item_code', 'machine_name');
            });
        } else {
            Schema::table('files', function (Blueprint $table) {
                DB::statement('ALTER TABLE `files` CHANGE `item_code` `machine_name` VARCHAR(255) null');
            });
        }
    }
};
