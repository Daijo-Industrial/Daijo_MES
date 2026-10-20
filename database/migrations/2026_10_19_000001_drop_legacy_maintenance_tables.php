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
        Schema::dropIfExists('maintenance_machines');
        Schema::dropIfExists('maintenance_moulds');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('maintenance_machines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('tanggal');
            $table->string('tipe');
            $table->string('no_mesin');
            $table->string('jenis_kerusakan');
            $table->text('perbaikan');
            $table->string('lama_pengerjaan')->nullable();
            $table->string('pic');
            $table->boolean('status')->default(false);
            $table->text('remark')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_moulds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('tanggal');
            $table->string('tipe');
            $table->string('part_no');
            $table->string('part_name');
            $table->string('jenis_kerusakan');
            $table->text('perbaikan');
            $table->string('lama_pengerjaan')->nullable();
            $table->string('pic');
            $table->boolean('status')->default(false);
            $table->text('remark')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }
};
