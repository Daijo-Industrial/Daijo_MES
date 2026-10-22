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
        Schema::create('inspection_reports', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->index();
            $table->string('customer');
            $table->date('inspection_date')->index();
            $table->string('part_number')->index();
            $table->string('part_name');
            $table->double('weight');
            $table->string('weight_uom');
            $table->string('material');
            $table->string('color');
            $table->string('tool_number_or_cav_number');
            $table->string('machine_number');
            $table->unsignedTinyInteger('shift');
            $table->string('operator')->nullable();
            $table->string('inspector')->default('-');
            $table->string('inspector_autograph')->nullable();
            $table->string('leader_autograph')->nullable();
            $table->string('head_autograph')->nullable();
            $table->timestamps();

            $table->index(['part_number', 'inspection_date'], 'ir_part_date_idx');
        });

        Schema::create('detail_inspection_reports', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_report_document_number')->index('dir_report_doc_num_idx');
            $table->string('document_number')->index();
            $table->unsignedTinyInteger('period');
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->timestamps();

            $table->index(['inspection_report_document_number', 'period'], 'dir_report_period_idx');
        });

        Schema::create('first_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('detail_inspection_report_document_number')->index('fi_detail_doc_num_idx');
            $table->string('appearance');
            $table->string('remarks')->nullable();
            $table->double('weight');
            $table->string('weight_uom');
            $table->string('fitting_test')->nullable();
            $table->timestamps();
        });

        Schema::create('second_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('detail_inspection_report_document_number')->index('si_detail_doc_num_idx');
            $table->string('document_number')->index();
            $table->integer('lot_size_quantity')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_dimensions', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_report_document_number')->index('idim_report_doc_num_idx');
            $table->double('lower_limit')->nullable();
            $table->double('upper_limit')->nullable();
            $table->string('limit_uom')->nullable();
            $table->decimal('actual_value', 8, 3)->nullable();
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->string('judgement')->nullable();
            $table->string('area')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_defects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('department')->index();
            $table->timestamps();
        });

        Schema::create('inspection_problems', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_report_document_number')->index('iprob_report_doc_num_idx');
            $table->string('type');
            $table->time('time');
            $table->integer('cycle_time');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_packagings', function (Blueprint $table) {
            $table->id();
            $table->string('second_inspection_document_number')->index('ipack_second_doc_num_idx');
            $table->integer('snp');
            $table->string('box_label');
            $table->string('judgement');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_samplings', function (Blueprint $table) {
            $table->id();
            $table->string('second_inspection_document_number')->index('isamp_second_doc_num_idx');
            $table->integer('quantity');
            $table->string('box_label');
            $table->string('appearance');
            $table->integer('ng_quantity')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_quantities', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_report_document_number')->index('iqty_report_doc_num_idx');
            $table->integer('output_quantity');
            $table->integer('pass_quantity');
            $table->double('pass_rate');
            $table->integer('reject_quantity');
            $table->double('reject_rate');
            $table->integer('sampling_quantity');
            $table->integer('ng_sample_quantity');
            $table->double('ng_sample_rate');
            $table->timestamps();
        });

        Schema::create('inspection_judgements', function (Blueprint $table) {
            $table->id();
            $table->string('detail_inspection_report_document_number')->index('ijudg_detail_doc_num_idx');
            $table->integer('pass_quantity');
            $table->integer('reject_quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_judgements');
        Schema::dropIfExists('inspection_quantities');
        Schema::dropIfExists('inspection_samplings');
        Schema::dropIfExists('inspection_packagings');
        Schema::dropIfExists('inspection_problems');
        Schema::dropIfExists('inspection_defects');
        Schema::dropIfExists('inspection_dimensions');
        Schema::dropIfExists('second_inspections');
        Schema::dropIfExists('first_inspections');
        Schema::dropIfExists('detail_inspection_reports');
        Schema::dropIfExists('inspection_reports');
    }
};
