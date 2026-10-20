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
        Schema::create('master_moulds', function (Blueprint $table) {
            $table->id();
            $table->string('mould_no', 100)->index();
            $table->string('part_no', 100)->index();
            $table->string('part_name', 255);
            $table->string('model', 100)->nullable();
            $table->string('customer', 150)->index();
            $table->string('machine_number', 100)->nullable();
            $table->string('material', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['mould_no', 'part_no']);
            $table->index(['customer', 'mould_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_moulds');
    }
};
