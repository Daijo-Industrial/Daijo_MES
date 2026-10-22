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
        Schema::create('sp_production_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('sp_work_orders')->cascadeOnDelete();
            $table->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('unit_line');
            $table->string('shift');
            $table->string('status')->default('running'); // running, paused, completed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('total_input')->default(0);
            $table->integer('total_good')->default(0);
            $table->integer('total_reject')->default(0);
            $table->integer('total_rework_in')->default(0);
            $table->integer('total_rework_recovered')->default(0);
            $table->integer('total_scrap')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_production_sessions');
    }
};
