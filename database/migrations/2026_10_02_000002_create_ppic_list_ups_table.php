<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppic_list_ups', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('zone', 50)->default('ZONE A')->index();
            $table->string('status', 30)->default('DRAFT')->index(); // DRAFT, GENERATED
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('ppic_list_up_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_up_id')->constrained('ppic_list_ups')->cascadeOnDelete();
            $table->unsignedBigInteger('machine_id')->nullable()->index();
            $table->string('machine_name', 50)->index();
            $table->string('part_no', 100)->index();
            $table->string('description', 255)->nullable();
            $table->string('material_type', 150)->nullable();
            $table->string('spk_no', 100)->nullable();
            $table->integer('cavity')->default(1);
            $table->integer('cycle_time')->default(0);
            $table->integer('target_per_hour')->default(0);
            $table->integer('operator_shift_1')->default(0);
            $table->integer('operator_shift_2')->default(0);
            $table->integer('operator_shift_3')->default(0);
            $table->integer('qty_to_run')->default(0);
            $table->string('reason', 255)->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_generated')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppic_list_up_items');
        Schema::dropIfExists('ppic_list_ups');
    }
};
