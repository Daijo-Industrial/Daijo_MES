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
        // Master Item Checklist Fixed
        Schema::create('maintenance_check_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('period'); // Daily, Weekly, Two weeks
            $table->string('kriteria')->default('Predictive');
            $table->string('standard');
            $table->string('input_type')->default('ok_ng'); // ok_ng, numeric
            $table->string('unit')->nullable(); // °C, Kgf
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Header Pengecekan per Mesin & Tanggal Produksi (Reset jam 07:30 AM)
        Schema::create('maintenance_check_headers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('machine_id');
            $table->date('date');
            $table->string('check_time')->nullable();
            $table->string('prepared_by');
            $table->string('approved_by')->nullable();
            $table->string('status')->default('COMPLETED');
            $table->timestamps();

            $table->foreign('machine_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['machine_id', 'date']);
        });

        // Detail Hasil Pengecekan 17 Items
        Schema::create('maintenance_check_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('header_id');
            $table->unsignedBigInteger('item_id');
            $table->string('value'); // OK, NG, or numeric value string
            $table->boolean('is_normal')->default(true);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('header_id')->references('id')->on('maintenance_check_headers')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('maintenance_check_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_check_details');
        Schema::dropIfExists('maintenance_check_headers');
        Schema::dropIfExists('maintenance_check_items');
    }
};
