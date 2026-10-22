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
        Schema::create('custom_barcode_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('item_code')->index();
            $table->string('item_name')->nullable();
            $table->string('spk_number')->index();
            $table->integer('quantity');
            $table->string('warehouse')->default('WFI');
            $table->string('shift', 10);
            $table->integer('start_label');
            $table->integer('end_label');
            $table->integer('total_labels');
            $table->date('prod_date')->nullable();
            $table->string('operator')->nullable();
            $table->string('customer')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_barcode_logs');
    }
};
