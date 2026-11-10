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
        Schema::table('mould_checklists', function (Blueprint $table) {
            if (! Schema::hasColumn('mould_checklists', 'start_at')) {
                $table->timestamp('start_at')->nullable()->after('technician_name')->index();
            }
            if (! Schema::hasColumn('mould_checklists', 'end_at')) {
                $table->timestamp('end_at')->nullable()->after('start_at')->index();
            }
            if (! Schema::hasColumn('mould_checklists', 'is_ongoing')) {
                $table->boolean('is_ongoing')->default(false)->after('end_at')->index();
            }
            $table->string('part_name', 255)->nullable()->change();
            $table->string('customer', 150)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mould_checklists', function (Blueprint $table) {
            $table->dropColumn(['start_at', 'end_at', 'is_ongoing']);
            $table->string('part_name', 255)->nullable(false)->change();
            $table->string('customer', 150)->nullable(false)->change();
        });
    }
};

