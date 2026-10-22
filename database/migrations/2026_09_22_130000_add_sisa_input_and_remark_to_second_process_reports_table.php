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
        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->integer('sisa_input')->default(0)->after('jumlah_output');
            $table->text('sisa_input_remark')->nullable()->after('ng_remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->dropColumn(['sisa_input', 'sisa_input_remark']);
        });
    }
};
