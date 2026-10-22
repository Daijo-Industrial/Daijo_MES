<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->string('ipqc_lot_color')->nullable()->after('ng_remarks');
            $table->string('ipqc_std_glossy')->nullable()->after('ipqc_lot_color');
            $table->string('ipqc_std_viscosity')->nullable()->after('ipqc_std_glossy');
            $table->string('ipqc_std_oven_temp')->nullable()->after('ipqc_std_viscosity');
            $table->string('ipqc_product_color')->nullable()->after('ipqc_std_oven_temp');
            $table->string('ipqc_app_sample')->nullable()->after('ipqc_product_color');

            $table->json('ipqc_selected_measurements')->nullable()->after('ipqc_app_sample');

            $table->integer('ipqc_total_output')->default(0)->after('ipqc_selected_measurements');
            $table->integer('ipqc_total_sample')->default(0)->after('ipqc_total_output');
            $table->integer('ipqc_total_reject_sample')->default(0)->after('ipqc_total_sample');
            $table->decimal('ipqc_total_reject_rate', 5, 2)->default(0)->after('ipqc_total_reject_sample');
            $table->integer('ipqc_total_pass')->default(0)->after('ipqc_total_reject_rate');
            $table->integer('ipqc_total_reject')->default(0)->after('ipqc_total_pass');

            $table->string('ipqc_inspector_name')->nullable()->after('ipqc_total_reject');
            $table->string('ipqc_checker_name')->nullable()->after('ipqc_inspector_name');
            $table->string('ipqc_overall_judgement')->nullable()->after('ipqc_checker_name');
        });
    }

    public function down(): void
    {
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
                'ipqc_overall_judgement',
            ]);
        });
    }
};
