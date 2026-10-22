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
        Schema::table('second_process_materials', function (Blueprint $table) {
            $table->decimal('qty', 12, 4)->nullable()->change();
            $table->string('uom', 50)->nullable()->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('second_process_materials', function (Blueprint $table) {
            $table->dropColumn('uom');
            $table->integer('qty')->nullable()->change();
        });
    }
};
