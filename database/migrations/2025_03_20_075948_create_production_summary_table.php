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
        Schema::create('production_summary', function (Blueprint $table) {
            $table->id();
            $table->string('spk_code');
            $table->integer('total_quantity');
            $table->string('warehouse');
            $table->string('label')->default('all');
            $table->date('created_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_summary');
    }
};
