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
        Schema::create('master_business_partners', function (Blueprint $table) {
            $table->id();
            $table->string('bp_code', 100)->unique();
            $table->string('bp_name', 255)->index();
            $table->string('category', 50)->default('VENDOR')->index();
            $table->string('type', 100)->nullable();
            $table->decimal('balance', 18, 2)->default(0);
            $table->string('foreign_name', 255)->nullable()->index();
            $table->string('sales_employee', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_business_partners');
    }
};
