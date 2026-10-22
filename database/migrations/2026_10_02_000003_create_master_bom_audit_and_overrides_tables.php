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
        // 1. Audit Verifikasi PE untuk Parent Item / BOM
        if (!Schema::hasTable('master_bom_verifications')) {
            Schema::create('master_bom_verifications', function (Blueprint $table) {
                $table->id();
                $table->string('parent_item', 100)->unique()->index();
                $table->timestamp('verified_at');
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->string('verified_by_name', 150)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 1b. Log Riwayat Verifikasi Ulang Audit PE
        if (!Schema::hasTable('master_bom_verification_logs')) {
            Schema::create('master_bom_verification_logs', function (Blueprint $table) {
                $table->id();
                $table->string('parent_item', 100)->index();
                $table->timestamp('verified_at');
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->string('verified_by_name', 150)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. Override Tipe Material Khusus Engineering / PE
        if (!Schema::hasTable('master_bom_material_overrides')) {
            Schema::create('master_bom_material_overrides', function (Blueprint $table) {
                $table->id();
                $table->string('item_code', 100)->unique()->index();
                $table->string('item_type', 30)->comment('RAW_MATERIAL, RESIN, CHEMICAL, HARDWARE, PACKAGING');
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->string('updated_by_name', 150)->nullable();
                $table->timestamps();
            });
        }

        // 3. Tambah kolom audit verifikasi ke tabel header FG sah jika belum ada
        if (Schema::hasTable('master_bom_fg_headers')) {
            Schema::table('master_bom_fg_headers', function (Blueprint $table) {
                if (!Schema::hasColumn('master_bom_fg_headers', 'is_verified')) {
                    $table->boolean('is_verified')->default(false)->after('is_active');
                }
                if (!Schema::hasColumn('master_bom_fg_headers', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('is_verified');
                }
                if (!Schema::hasColumn('master_bom_fg_headers', 'verified_by_name')) {
                    $table->string('verified_by_name', 150)->nullable()->after('verified_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('master_bom_fg_headers')) {
            Schema::table('master_bom_fg_headers', function (Blueprint $table) {
                $table->dropColumn(['is_verified', 'verified_at', 'verified_by_name']);
            });
        }

        Schema::dropIfExists('master_bom_material_overrides');
        Schema::dropIfExists('master_bom_verifications');
    }
};
