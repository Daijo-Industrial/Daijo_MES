<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ipqc_inspection_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained('ipqc_inspections')->cascadeOnDelete();
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
            $table->unique(['inspection_id', 'hour_ke']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipqc_inspection_records');
    }
};
