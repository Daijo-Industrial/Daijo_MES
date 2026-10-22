<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('daily_item_codes') && !Schema::hasColumn('daily_item_codes', 'branch_id')) {
            Schema::table('daily_item_codes', function (Blueprint $table) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('machine_jobs') && !Schema::hasColumn('machine_jobs', 'branch_id')) {
            Schema::table('machine_jobs', function (Blueprint $table) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('item_code')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('mwh_warehouses') && !Schema::hasColumn('mwh_warehouses', 'branch_id')) {
            Schema::table('mwh_warehouses', function (Blueprint $table) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });

            // Map warehouse KBN and KRW to branches
            $jktBranch = DB::table('branches')->where('code', 'JKT')->first();
            $krwBranch = DB::table('branches')->where('code', 'KRW')->first();

            if ($jktBranch) {
                DB::table('mwh_warehouses')
                    ->where('whse_code', 'KBN')
                    ->update(['branch_id' => $jktBranch->id]);
            }

            if ($krwBranch) {
                DB::table('mwh_warehouses')
                    ->where('whse_code', 'KRW')
                    ->update(['branch_id' => $krwBranch->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mwh_warehouses') && Schema::hasColumn('mwh_warehouses', 'branch_id')) {
            Schema::table('mwh_warehouses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        if (Schema::hasTable('machine_jobs') && Schema::hasColumn('machine_jobs', 'branch_id')) {
            Schema::table('machine_jobs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        if (Schema::hasTable('daily_item_codes') && Schema::hasColumn('daily_item_codes', 'branch_id')) {
            Schema::table('daily_item_codes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
