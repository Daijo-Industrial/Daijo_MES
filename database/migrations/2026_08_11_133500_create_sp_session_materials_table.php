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
        Schema::create('sp_session_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sp_production_sessions')->cascadeOnDelete();
            $table->string('type'); // 'paint' or 'part'
            $table->string('item_name');
            $table->string('lot_number')->nullable();
            $table->string('visco')->nullable();       // paint only
            $table->string('mixing_ratio')->nullable(); // paint only
            $table->decimal('qty', 10, 2)->nullable();
            $table->string('uom')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_session_materials');
    }
};
