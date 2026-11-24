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
        // 1. Alter second_process_reports
        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('shift');
            $table->string('output_destination')->nullable()->after('status');
            $table->text('ng_remarks')->nullable()->after('production_notes');

            // Signatures timestamps (name columns already exist, we keep them or add others)
            $table->timestamp('created_by_signed_at')->nullable()->after('created_by_name');
            $table->timestamp('pqc_signed_at')->nullable()->after('pqc_name');
            $table->string('leader_name')->nullable()->after('pqc_signed_at');
            $table->timestamp('leader_signed_at')->nullable()->after('leader_name');
            $table->timestamp('acknowledged_signed_at')->nullable()->after('acknowledged_by_name');
        });

        // 2. Alter second_process_hourly_productions
        Schema::table('second_process_hourly_productions', function (Blueprint $table) {
            $table->integer('ng_qty')->default(0)->after('ok_qty');
            $table->text('remark')->nullable()->after('acumulasi_qty');
        });

        // 3. Alter second_process_materials
        Schema::table('second_process_materials', function (Blueprint $table) {
            $table->string('mixing_ratio')->nullable()->after('qty');
            $table->string('paint_type')->nullable()->after('mixing_ratio'); // primer, base_coat, top_coat
            $table->string('sub_type')->nullable()->after('paint_type'); // paint, thinner, hardener
        });

        // 4. Create second_process_ng_hourly_details
        Schema::create('second_process_ng_hourly_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ng_record_id')->constrained('second_process_ng_records')->onDelete('cascade');
            $table->integer('hour_ke');
            $table->integer('qty')->default(0);
            $table->timestamps();
        });

        // 5. Alter second_process_ng_records
        Schema::table('second_process_ng_records', function (Blueprint $table) {
            $table->string('ng_category')->nullable()->after('report_id'); // 'ng_buffing', 'ng_proses', 'ng_part'
            $table->text('remark')->nullable()->after('ng_input_qty');

            // Drop old hour columns
            $table->dropColumn([
                'hour_1', 'hour_2', 'hour_3', 'hour_4', 'hour_5', 'hour_6',
                'hour_7', 'hour_8', 'hour_9', 'hour_10', 'hour_11', 'hour_12',
            ]);
        });

        // 6. Alter second_process_troubles
        Schema::table('second_process_troubles', function (Blueprint $table) {
            $table->string('category')->nullable()->after('report_id'); // man, machine, lingkungan, part, pps
            $table->text('masalah')->nullable()->after('category');
            $table->integer('loss_time_minutes')->default(0)->after('penanganan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse trouble report changes
        Schema::table('second_process_troubles', function (Blueprint $table) {
            $table->dropColumn(['category', 'masalah', 'loss_time_minutes']);
        });

        // Re-add hour columns to second_process_ng_records and remove new columns
        Schema::table('second_process_ng_records', function (Blueprint $table) {
            $table->dropColumn(['ng_category', 'remark']);

            for ($h = 1; $h <= 12; $h++) {
                $table->integer('hour_' . $h)->default(0);
            }
        });

        // Drop hourly detail table
        Schema::dropIfExists('second_process_ng_hourly_details');

        // Reverse material changes
        Schema::table('second_process_materials', function (Blueprint $table) {
            $table->dropColumn(['mixing_ratio', 'paint_type', 'sub_type']);
        });

        // Reverse hourly production changes
        Schema::table('second_process_hourly_productions', function (Blueprint $table) {
            $table->dropColumn(['ng_qty', 'remark']);
        });

        // Reverse reports table changes
        Schema::table('second_process_reports', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'output_destination', 'ng_remarks',
                'created_by_signed_at', 'pqc_signed_at', 'leader_name',
                'leader_signed_at', 'acknowledged_signed_at',
            ]);
        });
    }
};
