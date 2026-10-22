<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_transfer_logs', function (Blueprint $table) {
            $table->string('ok_to_warehouse')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('qc_transfer_logs', function (Blueprint $table) {
            $table->string('ok_to_warehouse')->nullable(false)->change();
        });
    }
};
