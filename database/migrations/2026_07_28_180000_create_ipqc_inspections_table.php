<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ipqc_inspections', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('part_number');
            $table->string('shift');
            $table->string('unit_line');
            $table->string('process_prod')->nullable();
            $table->string('model')->nullable();
            $table->string('part_name')->nullable();
            $table->string('customer')->nullable();
            $table->string('lot_color')->nullable();
            $table->string('std_glossy')->nullable();
            $table->string('std_viscosity')->nullable();
            $table->string('std_oven_temp')->nullable();
            $table->string('product_color')->nullable();
            $table->string('app_sample')->nullable();
            $table->json('selected_measurements')->nullable();
            $table->integer('total_output')->default(0);
            $table->integer('total_sample')->default(0);
            $table->integer('total_reject_sample')->default(0);
            $table->decimal('total_reject_rate', 5, 2)->default(0);
            $table->integer('total_pass')->default(0);
            $table->integer('total_reject')->default(0);
            $table->string('inspector_name')->nullable();
            $table->string('checker_name')->nullable();
            $table->string('overall_judgement')->nullable();
            $table->string('status')->default('ongoing');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['date', 'part_number', 'shift', 'unit_line'], 'ipqc_unique_inspection');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipqc_inspections');
    }
};
