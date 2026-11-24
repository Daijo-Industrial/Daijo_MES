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
        Schema::create('asakai_pic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asakai_id')->constrained('asakai_master')->onDelete('cascade');
            $table->string('pic_name');
            $table->tinyInteger('order_no')->default(0);
            $table->timestamps();

            $table->index(['asakai_id', 'order_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asakai_pic');
    }
};
