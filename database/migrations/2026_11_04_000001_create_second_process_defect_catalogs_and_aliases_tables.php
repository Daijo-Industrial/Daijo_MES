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
        Schema::create('second_process_defect_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('second_process_defect_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('defect_catalog_id')
                ->constrained('second_process_defect_catalogs')
                ->onDelete('cascade');
            $table->string('alias')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('second_process_defect_aliases');
        Schema::dropIfExists('second_process_defect_catalogs');
    }
};
