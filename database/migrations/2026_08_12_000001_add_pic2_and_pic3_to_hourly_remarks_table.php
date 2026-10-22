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
        Schema::table('hourly_remarks', function (Blueprint $table) {
            $table->string('pic_2')->nullable()->after('pic');
            $table->string('pic_3')->nullable()->after('pic_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hourly_remarks', function (Blueprint $table) {
            $table->dropColumn(['pic_2', 'pic_3']);
        });
    }
};
