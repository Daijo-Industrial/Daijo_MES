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
        Schema::create('mould_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('doc_number', 100)->default('DI-F-P/PD/05/MTM-043');
            $table->string('revision', 10)->default('00');
            $table->date('check_date')->index();
            $table->foreignId('mould_id')->nullable()->constrained('master_moulds')->nullOnDelete();
            $table->string('mould_no', 100)->index();
            $table->string('part_no', 100)->index();
            $table->string('part_name', 255);
            $table->string('model', 100)->nullable();
            $table->string('customer', 150)->index();
            $table->string('machine_number', 100)->nullable();
            $table->string('technician_name', 255);
            $table->string('start_time', 10)->nullable();
            $table->string('end_time', 10)->nullable();
            $table->string('status', 20)->default('DRAFT')->index();

            // Signatures & Approval
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('issued_name', 100)->nullable();
            $table->timestamp('issued_at')->nullable();

            $table->foreignId('checked_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('checked_name', 100)->nullable();
            $table->timestamp('checked_at')->nullable();

            $table->text('rejection_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['check_date', 'status']);
        });

        Schema::create('mould_checklist_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mould_checklist_id')->constrained('mould_checklists')->cascadeOnDelete();
            $table->unsignedInteger('item_no');
            $table->string('point_check', 150);
            $table->string('cleaning_status', 10)->default('-');
            $table->string('lubricate_status', 10)->default('-');
            $table->string('remark', 255)->nullable();
            $table->timestamps();

            $table->index(['mould_checklist_id', 'item_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mould_checklist_details');
        Schema::dropIfExists('mould_checklists');
    }
};
