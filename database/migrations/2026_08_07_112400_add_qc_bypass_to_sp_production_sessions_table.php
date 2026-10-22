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
        Schema::table('sp_production_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sp_production_sessions', 'is_qc_bypassed')) {
                $table->boolean('is_qc_bypassed')->default(false)->after('remarks');
                $table->string('qc_bypass_reason')->nullable()->after('is_qc_bypassed');
                $table->timestamp('qc_bypassed_at')->nullable()->after('qc_bypass_reason');
                $table->foreignId('qc_bypassed_by')->nullable()->constrained('users')->nullOnDelete()->after('qc_bypassed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sp_production_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sp_production_sessions', 'is_qc_bypassed')) {
                $table->dropForeign(['qc_bypassed_by']);
                $table->dropColumn(['is_qc_bypassed', 'qc_bypass_reason', 'qc_bypassed_at', 'qc_bypassed_by']);
            }
        });
    }
};
