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
        Schema::create('production_material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->string('item_code')->index();
            $table->string('item_name')->nullable();
            $table->decimal('qty_requested', 12, 2);
            $table->string('uom', 20)->default('KG');
            $table->string('machine_no', 50)->nullable();
            $table->string('spk_code', 50)->nullable();
            $table->string('urgency', 20)->default('NORMAL'); // NORMAL, URGENT
            $table->dateTime('needed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('PENDING'); // PENDING, PROCESSED, REJECTED, CANCELLED

            // Requester
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('requested_by_name')->nullable();

            // Fulfillment (Store)
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->string('processed_by_name')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->unsignedBigInteger('outgoing_id')->nullable();
            $table->string('outgoing_code', 50)->nullable();

            // Rejection (Store)
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->string('rejected_by_name')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->text('reject_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_material_requests');
    }
};

