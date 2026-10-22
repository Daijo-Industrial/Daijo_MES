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
        Schema::create('master_boms', function (Blueprint $table) {
            $table->id();
            $table->string('sap_line_id', 50)->nullable()->comment('Index/ID line dari SAP (#)');
            $table->string('parent_item', 100)->index()->comment('Kode FG atau WIP (Father Item)');
            $table->string('parent_description', 255)->nullable()->comment('Deskripsi Parent Item');
            $table->string('component_item', 100)->index()->comment('Kode Bahan/WIP Komponen (Child Item)');
            $table->string('component_description', 255)->nullable()->comment('Deskripsi Komponen');
            $table->decimal('quantity', 16, 6)->default(0)->comment('Kebutuhan bahan per 1 unit Parent');
            $table->string('uom', 20)->nullable()->comment('Satuan bahan (PCS, KG, LT, dll)');
            $table->boolean('is_active')->default(true)->comment('Status aktif BOM');
            $table->timestamps();

            // Composite index untuk mempercepat pencarian relasi spesifik
            $table->index(['parent_item', 'component_item']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_boms');
    }
};
