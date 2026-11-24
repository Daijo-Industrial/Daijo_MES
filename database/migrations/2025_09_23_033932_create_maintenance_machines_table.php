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
        Schema::create('maintenance_machines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('tanggal');
            $table->string('tipe'); // kalau mau tetap ada tipe repair/maintenance
            $table->string('mesin');
            $table->string('jenis_kerusakan');
            $table->text('perbaikan');
            $table->string('lama_pengerjaan')->nullable(); // nanti bisa diisi jam:menit
            $table->string('pic');
            $table->boolean('status')->default(false);
            $table->text('remark')->nullable();
            $table->timestamp('finished_at')->nullable(); // baru, untuk track kapan selesai
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_machines');
    }
};
