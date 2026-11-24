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
        Schema::create('asakai_rca', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asakai_id')->constrained('asakai_master')->onDelete('cascade');
            $table->tinyInteger('why_level'); // 1-5
            $table->text('description');
            $table->tinyInteger('order_no')->default(0);
            $table->timestamps();

            $table->index(['asakai_id', 'why_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asakai_rca');
    }
};
