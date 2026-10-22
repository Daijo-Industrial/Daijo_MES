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
        Schema::create('spk_change_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_batch_id')->index();
            $table->string('spk_number')->index();
            $table->string('item_code')->nullable()->index();
            $table->enum('change_type', ['NEW', 'QTY_CHANGE', 'STATUS_CHANGE', 'REMOVED'])->default('NEW');
            $table->integer('old_planned_qty')->nullable();
            $table->integer('new_planned_qty')->nullable();
            $table->integer('old_completed_qty')->nullable();
            $table->integer('new_completed_qty')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spk_change_logs');
    }
};
