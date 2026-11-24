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
        Schema::create('asakai_master', function (Blueprint $table) {
            $table->id();
            $table->string('customer');
            $table->string('part_no');
            $table->text('issue');
            $table->integer('quantity');

            // Lot Date
            $table->enum('lot_shift', ['Shift 1', 'Shift 2', 'Shift 3']);
            $table->date('lot_date');

            $table->date('date_issue');

            // Single Text Fields
            $table->text('pokayoke')->nullable();
            $table->text('verify')->nullable();
            $table->text('fmea_cp')->nullable();
            $table->text('std_work')->nullable();

            // Dates
            $table->date('audit_date')->nullable();
            $table->date('reply_date')->nullable();

            // Status & Tracking
            $table->enum('status', ['draft', 'submitted', 'closed'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Untuk safety, jangan hard delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asakai_master');
    }
};
