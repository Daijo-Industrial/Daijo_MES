<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('second_process_ipqc_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('second_process_reports')->cascadeOnDelete();
            $table->integer('hour_ke');

            $table->json('appearance_checks')->nullable();
            $table->json('condition_checks')->nullable();
            $table->json('measurements')->nullable();

            $table->string('fitting_test')->nullable();
            $table->string('tape_test_judgement')->nullable();

            $table->integer('output_qty')->default(0);
            $table->integer('sample_qty')->default(0);
            $table->integer('reject_sample_qty')->default(0);
            $table->decimal('reject_rate', 5, 2)->default(0);
            $table->integer('pass_qty')->default(0);
            $table->integer('reject_qty')->default(0);

            $table->string('judgement')->nullable();

            $table->timestamps();

            $table->unique(['report_id', 'hour_ke']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_process_ipqc_records');
    }
};
