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
        Schema::create('spk_item_histories', function (Blueprint $table) {
            $table->id();
            $table->string('spk_number', 50);
            $table->string('item_code', 100);

            // index buat search cepat
            $table->index('spk_number');
            $table->index('item_code');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spk_item_histories');
    }
};
