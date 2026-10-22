<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('first_piece_inspections', function (Blueprint $table) {
            $table->string('model')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('first_piece_inspections', function (Blueprint $table) {
            $table->string('model')->nullable(false)->change();
        });
    }
};
