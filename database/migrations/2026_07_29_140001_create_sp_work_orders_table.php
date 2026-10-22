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
        Schema::create('sp_work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_number')->unique();
            $table->date('planned_date');
            $table->string('unit_line');
            $table->string('shift');
            $table->string('process_prod');
            $table->string('part_number');
            $table->string('part_name');
            $table->string('model')->nullable();
            $table->string('customer');
            $table->integer('target_qty')->default(0);
            $table->string('status')->default('planned'); // planned, in_progress, completed, cancelled
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_work_orders');
    }
};
