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
        Schema::create('mwh_incoming_headers', function (Blueprint $table) {
            $table->id();
            $table->string('document_no')->unique();
            $table->string('supplier_name')->nullable();
            $table->string('po_number')->nullable();
            $table->date('arrival_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mwh_pallets', function (Blueprint $table) {
            $table->id();
            $table->string('pallet_id')->unique();
            $table->foreignId('incoming_header_id')->nullable()->constrained('mwh_incoming_headers')->nullOnDelete();
            $table->string('item_code');
            $table->string('lot_no')->nullable();
            $table->decimal('initial_qty', 12, 2)->default(0);
            $table->decimal('current_qty', 12, 2)->default(0);
            $table->string('uom', 20)->default('KG');
            $table->foreignId('position_id')->nullable()->constrained('mwh_positions')->nullOnDelete();
            $table->enum('status', ['STORED', 'PARTIAL', 'EMPTY'])->default('STORED');
            $table->timestamps();
            $table->softDeletes();

            $table->index('item_code');
        });

        Schema::create('mwh_outgoings', function (Blueprint $table) {
            $table->id();
            $table->string('outgoing_code')->unique();
            $table->string('pallet_id');
            $table->foreignId('position_id')->nullable()->constrained('mwh_positions')->nullOnDelete();
            $table->string('item_code');
            $table->decimal('qty_taken', 12, 2)->default(0);
            $table->string('uom', 20)->default('KG');
            $table->date('outgoing_date');
            $table->string('issued_to')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('pallet_id');
            $table->index('item_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mwh_outgoings');
        Schema::dropIfExists('mwh_pallets');
        Schema::dropIfExists('mwh_incoming_headers');
    }
};
