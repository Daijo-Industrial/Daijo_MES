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
        Schema::create('mwh_daily_audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_code')->unique();
            $table->date('audit_date');
            $table->foreignId('whse_id')->nullable()->constrained('mwh_warehouses')->nullOnDelete();
            $table->foreignId('auditor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditor_name')->nullable();
            $table->integer('total_items')->default(0);
            $table->integer('matched_items')->default(0);
            $table->integer('discrepant_items')->default(0);
            $table->string('status', 30)->default('COMPLETED'); // DRAFT, COMPLETED
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['audit_date', 'whse_id']);
        });

        Schema::create('mwh_daily_audit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_audit_id')->constrained('mwh_daily_audits')->cascadeOnDelete();
            $table->string('pallet_id')->nullable();
            $table->foreignId('position_id')->nullable()->constrained('mwh_positions')->nullOnDelete();
            $table->string('position_code');
            $table->string('rack_code')->nullable();
            $table->string('system_item_code')->nullable();
            $table->string('system_item_name')->nullable();
            $table->string('system_lot_no')->nullable();
            $table->decimal('system_qty', 12, 2)->default(0);
            $table->string('uom', 20)->default('KG');
            $table->string('actual_item_code')->nullable();
            $table->decimal('actual_qty', 12, 2)->nullable();
            $table->decimal('diff_qty', 12, 2)->default(0);
            $table->text('discrepancy_reason')->nullable();
            $table->string('status', 30)->default('PENDING'); // MATCH, DISCREPANCY, PENDING
            $table->timestamps();

            $table->index('pallet_id');
            $table->index('position_code');
            $table->index('system_item_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mwh_daily_audit_items');
        Schema::dropIfExists('mwh_daily_audits');
    }
};
