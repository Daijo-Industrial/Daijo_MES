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
        // 1. Real-time production entries
        Schema::create('sp_production_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sp_production_sessions')->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->integer('good_qty')->default(0);
            $table->integer('reject_qty')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 2. Reject defect details
        Schema::create('sp_reject_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sp_production_sessions')->cascadeOnDelete();
            $table->string('defect_type');
            $table->integer('quantity')->default(0);
            $table->string('cause')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 3. Rework entries
        Schema::create('sp_rework_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sp_production_sessions')->cascadeOnDelete();
            $table->integer('input_qty')->default(0);
            $table->integer('recovered_qty')->default(0);
            $table->integer('scrapped_qty')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 4. Downtime entries
        Schema::create('sp_downtime_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sp_production_sessions')->cascadeOnDelete();
            $table->string('reason');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('resume_time')->nullable();
            $table->integer('duration_minutes')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // 5. Input quantity entries (from injection/WIP)
        Schema::create('sp_input_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('sp_production_sessions')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->string('source')->default('manual'); // manual, barcode
            $table->string('pallet_number')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_input_entries');
        Schema::dropIfExists('sp_downtime_entries');
        Schema::dropIfExists('sp_rework_entries');
        Schema::dropIfExists('sp_reject_entries');
        Schema::dropIfExists('sp_production_entries');
    }
};
