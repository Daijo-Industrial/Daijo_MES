<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_summary', function (Blueprint $table) {
            $table->tinyInteger('qc_status')->default(0)->after('sap_sent_at');
            // 0 = Belum diinspeksi
            // 1 = Selesai (semua box sudah di-transfer)
            // 2 = Sebagian (ada box yang sudah diproses, ada yang belum)
        });
    }

    public function down(): void
    {
        Schema::table('production_summary', function (Blueprint $table) {
            $table->dropColumn('qc_status');
        });
    }
};
