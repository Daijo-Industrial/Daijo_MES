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
        Schema::create('spk_bom_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('spk_number', 50)->index();
            $table->string('action_type', 30); // UPDATE_QTY, ADD_MATERIAL, DELETE_MATERIAL, REPLACE_MATERIAL, BATCH
            $table->string('item_code', 100)->nullable()->index();
            $table->string('item_name', 255)->nullable();
            $table->string('replaced_item_code', 100)->nullable();
            $table->decimal('base_qty', 16, 6)->nullable();
            $table->decimal('plan_qty', 16, 4)->nullable();
            $table->decimal('old_plan_qty', 16, 4)->nullable();
            $table->string('warehouse', 50)->nullable();
            $table->string('status', 20)->default('SUCCESS'); // SUCCESS, FAILED
            $table->string('message', 255)->nullable();
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_name', 100)->nullable();
            $table->timestamps();

            $table->index(['spk_number', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spk_bom_change_logs');
    }
};
