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
        // 1. Table Header: Khusus Menyimpan Master Finished Goods (FG) Sah
        Schema::create('master_bom_fg_headers', function (Blueprint $table) {
            $table->id();
            $table->string('fg_item_code')->unique()->comment('Kode Part FG Resmi (e.g. D0B29100.)');
            $table->string('fg_description')->nullable()->comment('Deskripsi Part FG');
            $table->string('project_code')->nullable()->comment('Nama Project / Kategori SAP (e.g. SHAD TOP CASE, KULKAS SHARP)');
            $table->string('customer_name')->nullable()->comment('Customer (e.g. SHAD, YAMAHA, HONDA)');
            $table->string('uom', 20)->default('PCS')->comment('Satuan FG (PCS, UNIT)');
            $table->unsignedInteger('total_wip_count')->default(0)->comment('Jumlah part WIP / sub-assembly di dalam FG ini');
            $table->unsignedInteger('total_raw_count')->default(0)->comment('Jumlah raw material / chemical / packaging');
            $table->unsignedTinyInteger('max_depth_level')->default(1)->comment('Tingkat kedalaman pohon terdalam (1, 2, 3, 4, ...)');
            $table->boolean('has_packaging')->default(false)->comment('Apakah memiliki komponen box/kemasan');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('project_code');
            $table->index('customer_name');
            $table->index('is_active');
        });

        // 2. Table Detail Multilevel: Menyimpan seluruh struktur WIP, Sub-WIP, hingga Raw Materials
        Schema::create('master_bom_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fg_id')->constrained('master_bom_fg_headers')->cascadeOnDelete();
            $table->string('parent_item')->comment('Kode atasan langsung (immediate father)');
            $table->string('component_item')->comment('Kode part komponen ini');
            $table->string('component_description')->nullable();
            $table->unsignedTinyInteger('depth_level')->default(1)->comment('Kedalaman tingkat: 1=Lv1, 2=Lv2 (WIP), 3=Lv3 (WIP dlm WIP), dst');
            $table->string('item_type', 30)->default('RAW_MATERIAL')->comment('WIP, RESIN, CHEMICAL, PACKAGING, HARDWARE, RAW_MATERIAL');
            $table->boolean('is_wip')->default(false)->comment('True jika butuh SPK sendiri (punya resep komponen lagi)');
            $table->decimal('unit_qty', 16, 6)->default(0)->comment('Kebutuhan per 1 unit parent langsungnya');
            $table->decimal('total_qty_per_fg', 16, 6)->default(0)->comment('Akumulasi kebutuhan total per 1 unit FG induk utama');
            $table->string('uom', 20)->default('PCS');
            $table->text('lineage_path')->nullable()->comment('Silsilah hierarki: FG > WIP_1 > WIP_2 > Component');
            $table->timestamps();

            // Indexes untuk query super cepat saat pembuatan SPK & Tarik Gudang
            $table->index(['fg_id', 'is_wip']);
            $table->index(['fg_id', 'depth_level']);
            $table->index('parent_item');
            $table->index('component_item');
            $table->index('is_wip');
            $table->index('item_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_bom_components');
        Schema::dropIfExists('master_bom_fg_headers');
    }
};
