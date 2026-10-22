<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mwh_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('whse_code')->unique();
            $table->string('whse_name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mwh_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whse_id')->constrained('mwh_warehouses');
            $table->string('rack_code');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mwh_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->constrained('mwh_racks');
            $table->integer('level_no');
            $table->integer('slot_no');
            $table->string('position_code')->unique();
            $table->string('slot_label')->nullable();
            $table->string('status')->default('EMPTY'); // EMPTY, PARTIAL, FULL
            $table->string('last_item_code')->nullable();
            $table->integer('max_capacity')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mwh_positions');
        Schema::dropIfExists('mwh_racks');
        Schema::dropIfExists('mwh_warehouses');
    }
};
