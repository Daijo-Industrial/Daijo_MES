<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('second_process_ipqc_records');

        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->dropColumn([
                'ipqc_lot_color',
                'ipqc_std_glossy',
                'ipqc_std_viscosity',
                'ipqc_std_oven_temp',
                'ipqc_product_color',
                'ipqc_app_sample',
                'ipqc_selected_measurements',
                'ipqc_total_output',
                'ipqc_total_sample',
                'ipqc_total_reject_sample',
                'ipqc_total_reject_rate',
                'ipqc_total_pass',
                'ipqc_total_reject',
                'ipqc_inspector_name',
                'ipqc_checker_name',
                'ipqc_overall_judgement'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->string('ipqc_lot_color')->nullable();
            $table->string('ipqc_std_glossy')->nullable();
            $table->string('ipqc_std_viscosity')->nullable();
            $table->string('ipqc_std_oven_temp')->nullable();
            $table->string('ipqc_product_color')->nullable();
            $table->string('ipqc_app_sample')->nullable();
            $table->json('ipqc_selected_measurements')->nullable();
            $table->integer('ipqc_total_output')->default(0);
            $table->integer('ipqc_total_sample')->default(0);
            $table->integer('ipqc_total_reject_sample')->default(0);
            $table->decimal('ipqc_total_reject_rate', 5, 2)->default(0);
            $table->integer('ipqc_total_pass')->default(0);
            $table->integer('ipqc_total_reject')->default(0);
            $table->string('ipqc_inspector_name')->nullable();
            $table->string('ipqc_checker_name')->nullable();
            $table->string('ipqc_overall_judgement')->nullable();
        });

        Schema::create('second_process_ipqc_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('second_process_report_id')->constrained()->cascadeOnDelete();
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
            $table->unique(['second_process_report_id', 'hour_ke'], 'sp_ipqc_unique_hour');
        });
    }
};
