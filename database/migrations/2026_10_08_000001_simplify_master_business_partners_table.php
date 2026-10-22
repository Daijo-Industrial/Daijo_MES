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
        Schema::table('master_business_partners', function (Blueprint $table) {
            $table->string('group_code', 20)->nullable()->after('bp_name')->index();
            $table->dropColumn(['balance', 'sales_employee']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_business_partners', function (Blueprint $table) {
            $table->dropColumn('group_code');
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('sales_employee', 255)->nullable();
        });
    }
};
