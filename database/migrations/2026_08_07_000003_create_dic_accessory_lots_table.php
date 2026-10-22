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
        Schema::create('dic_accessory_lots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dic_id');
            $table->string('accessory_name'); // Jenis Accessories (alphanumeric text)
            $table->string('accessory_lot');  // Lot Accessories (alphanumeric + symbol)
            $table->timestamps();

            $table->foreign('dic_id')->references('id')->on('daily_item_codes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dic_accessory_lots');
    }
};
