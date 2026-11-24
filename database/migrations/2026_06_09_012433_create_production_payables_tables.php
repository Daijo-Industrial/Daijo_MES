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
        Schema::create('production_payables', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Document Reference
            $table->bigInteger('document_number')->unique()->comment('Nomor dokumen utang dari SAP');

            // Dates (YYYY-MM-DD format)
            $table->date('posting_date')->comment('Tanggal posting ke sistem');
            $table->date('value_date')->comment('Tanggal nilai untuk accounting');

            // Material/Item Info
            $table->string('item_no', 50)->comment('Nomor material/item');
            $table->text('item_description')->comment('Deskripsi lengkap item/service');

            // Quantity (integer only, no decimal)
            $table->integer('quantity')->comment('Jumlah item');

            // Remarks & Status
            $table->text('remarks')->nullable()->comment('Keterangan/catatan khusus');
            $table->enum('status', ['pending', 'received', 'invoiced', 'paid', 'cancelled'])->default('pending')->comment('Status pembayaran');

            // Audit Trails
            $table->timestamps();
            $table->softDeletes();
            $table->string('uploaded_by')->nullable()->comment('User yang upload');

            // Indexes for performance
            $table->index('document_number');
            $table->index('item_no');
            $table->index('posting_date');
            $table->index('status');
            $table->index('value_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_payables');
    }
};
