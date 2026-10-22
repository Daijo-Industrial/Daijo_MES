<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('first_piece_inspections', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('model')->nullable();
            $table->string('part_name');
            $table->string('part_number');
            $table->string('paint_code')->nullable();
            $table->string('thinner_code')->nullable();
            $table->string('ink_code')->nullable();
            $table->string('viscosity')->nullable();
            $table->string('cycle_time')->nullable();
            $table->string('time_submit')->nullable();

            $table->json('check_results')->nullable();
            $table->string('overall_judgement')->default('NG');
            $table->text('remark')->nullable();

            $table->string('prepared_by')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->string('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('first_piece_inspections');
    }
};
