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
        Schema::table('production_summary', function (Blueprint $table) {
            $table->boolean('sap_sent')->default(0)->after('label');
            $table->datetime('sap_sent_at')->nullable()->after('sap_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_summary', function (Blueprint $table) {
            $table->dropColumn(['sap_sent', 'sap_sent_at']);
        });
    }
};
