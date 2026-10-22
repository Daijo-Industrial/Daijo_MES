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
        Schema::create('master_list_materials', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 100)->unique();
            $table->text('item_description')->nullable();
            $table->string('preferred_supplier', 100)->nullable();
            $table->string('purchasing_uom', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_list_materials');
    }
};
