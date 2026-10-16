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
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');

        if (Schema::connection($connection)->hasTable($tableName)) {
            Schema::connection($connection)->table($tableName, function (Blueprint $table) {
                $table->string('subject_id', 191)->nullable()->change();
                $table->string('causer_id', 191)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('activitylog.database_connection');
        $tableName = config('activitylog.table_name');

        if (Schema::connection($connection)->hasTable($tableName)) {
            Schema::connection($connection)->table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('subject_id')->nullable()->change();
                $table->unsignedBigInteger('causer_id')->nullable()->change();
            });
        }
    }
};
