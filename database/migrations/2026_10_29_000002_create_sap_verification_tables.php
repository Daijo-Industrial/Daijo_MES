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
        if (! Schema::hasTable('sap_verification_batches')) {
            Schema::create('sap_verification_batches', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20)->index(); // 'RECEIPT' or 'PALLET'
                $table->string('file_name');
                $table->string('file_path')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->integer('total_items')->default(0);
                $table->decimal('total_sap_qty', 15, 2)->default(0);
                $table->decimal('total_mes_qty', 15, 2)->default(0);
                $table->integer('match_count')->default(0);
                $table->integer('diff_count')->default(0);
                $table->integer('missing_count')->default(0);
                $table->integer('only_sap_count')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('sap_verification_items')) {
            Schema::create('sap_verification_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('batch_id')->constrained('sap_verification_batches')->cascadeOnDelete();
                $table->string('ref_code', 100)->index(); // No. SPK atau No. Pallet
                $table->string('item_code', 100)->nullable()->index();
                $table->string('description', 255)->nullable();
                $table->string('warehouse', 50)->nullable();
                $table->decimal('sap_qty', 15, 2)->default(0);
                $table->decimal('mes_qty', 15, 2)->default(0);
                $table->decimal('diff_qty', 15, 2)->default(0);
                $table->string('status', 30)->index(); // 'MATCH', 'QTY_DIFF', 'MISSING_IN_SAP', 'ONLY_IN_SAP'
                $table->string('status_label', 100)->nullable();
                $table->json('sap_docs')->nullable();
                $table->json('sap_dates')->nullable();
                $table->json('mes_dates')->nullable();
                $table->json('raw_data')->nullable(); // detail sap_lines & mes_lines
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sap_verification_items');
        Schema::dropIfExists('sap_verification_batches');
    }
};
