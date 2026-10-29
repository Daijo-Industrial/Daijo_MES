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
        Schema::create('production_store_handovers', function (Blueprint $table) {
            $table->id();
            $table->date('production_date')->index();
            $table->tinyInteger('shift')->default(1)->index();
            $table->string('item_code', 100)->index();
            $table->string('item_name')->nullable();
            $table->string('customer_name')->nullable();

            // Qty metrics
            $table->integer('operator_actual_qty')->default(0); // Input actual operator mesin
            $table->integer('scanned_qty')->default(0);          // Total pcs barcode di-scan produksi
            $table->integer('scanned_boxes')->default(0);        // Total box barcode di-scan produksi

            // Store Handover metrics (Progressive running receipt)
            $table->integer('store_received_boxes')->default(0); // Akumulasi box diterima store
            $table->integer('store_received_qty')->default(0);   // Akumulasi pcs diterima store

            // Status & Audit
            $table->string('status', 30)->default('PENDING')->index(); // PENDING, PARTIAL, COMPLETED
            $table->foreignId('last_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('last_verified_by_name')->nullable();
            $table->dateTime('last_verified_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['production_date', 'shift', 'item_code'], 'prod_store_handover_unique');
        });

        Schema::create('production_store_handover_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handover_id')->constrained('production_store_handovers')->cascadeOnDelete();
            $table->integer('received_boxes')->default(0);
            $table->integer('received_qty')->default(0);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verified_by_name')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_store_handover_logs');
        Schema::dropIfExists('production_store_handovers');
    }
};
