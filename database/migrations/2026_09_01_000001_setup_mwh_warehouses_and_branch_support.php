<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Seed or ensure KBN & KRW warehouses exist
        if (Schema::hasTable('mwh_warehouses')) {
            $kbn = DB::table('mwh_warehouses')->where('whse_code', 'KBN')->first();
            if (!$kbn) {
                // If legacy MTR-01 exists, rename to KBN, else create
                $legacy = DB::table('mwh_warehouses')->where('whse_code', 'MTR-01')->first();
                if ($legacy) {
                    DB::table('mwh_warehouses')->where('id', $legacy->id)->update([
                        'whse_code'  => 'KBN',
                        'whse_name'  => 'Gudang Material KBN',
                        'updated_at' => now(),
                    ]);
                    $kbnId = $legacy->id;
                } else {
                    $kbnId = DB::table('mwh_warehouses')->insertGetId([
                        'whse_code'  => 'KBN',
                        'whse_name'  => 'Gudang Material KBN',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                $kbnId = $kbn->id;
            }

            $krw = DB::table('mwh_warehouses')->where('whse_code', 'KRW')->first();
            if (!$krw) {
                DB::table('mwh_warehouses')->insertGetId([
                    'whse_code'  => 'KRW',
                    'whse_name'  => 'Gudang Material Karawang',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 2. Add whse_id to mwh_incoming_headers
        if (Schema::hasTable('mwh_incoming_headers')) {
            Schema::table('mwh_incoming_headers', function (Blueprint $table) {
                if (!Schema::hasColumn('mwh_incoming_headers', 'whse_id')) {
                    $table->foreignId('whse_id')->nullable()->after('id')->constrained('mwh_warehouses')->nullOnDelete();
                }
            });
        }

        // 3. Add whse_id to mwh_pallets
        if (Schema::hasTable('mwh_pallets')) {
            Schema::table('mwh_pallets', function (Blueprint $table) {
                if (!Schema::hasColumn('mwh_pallets', 'whse_id')) {
                    $table->foreignId('whse_id')->nullable()->after('id')->constrained('mwh_warehouses')->nullOnDelete();
                }
            });
        }

        // 4. Add whse_id to mwh_outgoings
        if (Schema::hasTable('mwh_outgoings')) {
            Schema::table('mwh_outgoings', function (Blueprint $table) {
                if (!Schema::hasColumn('mwh_outgoings', 'whse_id')) {
                    $table->foreignId('whse_id')->nullable()->after('id')->constrained('mwh_warehouses')->nullOnDelete();
                }
            });
        }

        // 5. Backfill existing records to KBN warehouse if whse_id is null
        $kbnWhse = DB::table('mwh_warehouses')->where('whse_code', 'KBN')->first();
        if ($kbnWhse) {
            DB::table('mwh_racks')->whereNull('whse_id')->update(['whse_id' => $kbnWhse->id]);
            DB::table('mwh_incoming_headers')->whereNull('whse_id')->update(['whse_id' => $kbnWhse->id]);
            DB::table('mwh_pallets')->whereNull('whse_id')->update(['whse_id' => $kbnWhse->id]);
            DB::table('mwh_outgoings')->whereNull('whse_id')->update(['whse_id' => $kbnWhse->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mwh_outgoings') && Schema::hasColumn('mwh_outgoings', 'whse_id')) {
            Schema::table('mwh_outgoings', function (Blueprint $table) {
                $table->dropForeign(['whse_id']);
                $table->dropColumn('whse_id');
            });
        }

        if (Schema::hasTable('mwh_pallets') && Schema::hasColumn('mwh_pallets', 'whse_id')) {
            Schema::table('mwh_pallets', function (Blueprint $table) {
                $table->dropForeign(['whse_id']);
                $table->dropColumn('whse_id');
            });
        }

        if (Schema::hasTable('mwh_incoming_headers') && Schema::hasColumn('mwh_incoming_headers', 'whse_id')) {
            Schema::table('mwh_incoming_headers', function (Blueprint $table) {
                $table->dropForeign(['whse_id']);
                $table->dropColumn('whse_id');
            });
        }
    }
};
