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
        Schema::create('adjust_machine_ng_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjust_machine_log_id')->constrained('adjust_machine_logs')->cascadeOnDelete();
            $table->foreignId('ng_type_id')->constrained('production_ng_types')->restrictOnDelete();
            $table->integer('ng_quantity')->default(0);
            $table->string('ng_remarks')->nullable();
            $table->timestamps();

            $table->index(['adjust_machine_log_id', 'ng_type_id'], 'adj_ng_log_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adjust_machine_ng_details');
    }
};
