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
        Schema::create('hourly_remarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dic_id'); // DailyItemCode ID
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('target');
            $table->integer('actual')->default(0);
            $table->string('remark')->nullable();
            $table->boolean('is_achieve')->default(false);
            $table->string('pic');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hourly_remarks');
    }
};
