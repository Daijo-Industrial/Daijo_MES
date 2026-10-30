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
        // 1. Tambah kolom crosscheck store pada tabel production_scanned_data
        if (Schema::hasTable('production_scanned_data')) {
            Schema::table('production_scanned_data', function (Blueprint $table) {
                if (! Schema::hasColumn('production_scanned_data', 'store_check_status')) {
                    $table->string('store_check_status', 30)->default('PENDING')->after('summary_id');
                }
                if (! Schema::hasColumn('production_scanned_data', 'store_checked_by')) {
                    $table->unsignedBigInteger('store_checked_by')->nullable()->after('store_check_status');
                }
                if (! Schema::hasColumn('production_scanned_data', 'store_checked_by_name')) {
                    $table->string('store_checked_by_name', 255)->nullable()->after('store_checked_by');
                }
                if (! Schema::hasColumn('production_scanned_data', 'store_checked_at')) {
                    $table->timestamp('store_checked_at')->nullable()->after('store_checked_by_name');
                }
                if (! Schema::hasColumn('production_scanned_data', 'store_notes')) {
                    $table->text('store_notes')->nullable()->after('store_checked_at');
                }
            });

            // Index pendukung
            try {
                Schema::table('production_scanned_data', function (Blueprint $table) {
                    $table->index('store_check_status', 'idx_psd_store_check_status');
                });
            } catch (\Throwable $e) {
                // Ignore if index already exists
            }
        }

        // 2. Buat tabel snapshot / rekap harian laporan crosscheck store
        if (! Schema::hasTable('store_crosscheck_daily_reports')) {
            Schema::create('store_crosscheck_daily_reports', function (Blueprint $table) {
                $table->id();
                $table->date('report_date');
                $table->string('plant', 20)->default('KBN'); // KBN, KARAWANG, ALL
                $table->string('shift', 10)->default('ALL'); // ALL, 1, 2, 3
                $table->integer('total_boxes')->default(0);
                $table->integer('total_qty')->default(0);
                $table->integer('found_boxes')->default(0);
                $table->integer('found_qty')->default(0);
                $table->integer('missing_boxes')->default(0);
                $table->integer('missing_qty')->default(0);
                $table->integer('pending_boxes')->default(0);
                $table->integer('pending_qty')->default(0);
                $table->decimal('accuracy_rate', 5, 2)->default(0.00);
                $table->string('status', 30)->default('OPEN'); // OPEN, FINALIZED
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->string('verified_by_name', 255)->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['report_date', 'plant', 'shift'], 'uniq_report_date_plant_shift');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('production_scanned_data')) {
            Schema::table('production_scanned_data', function (Blueprint $table) {
                $colsToDrop = [];
                if (Schema::hasColumn('production_scanned_data', 'store_notes')) $colsToDrop[] = 'store_notes';
                if (Schema::hasColumn('production_scanned_data', 'store_checked_at')) $colsToDrop[] = 'store_checked_at';
                if (Schema::hasColumn('production_scanned_data', 'store_checked_by_name')) $colsToDrop[] = 'store_checked_by_name';
                if (Schema::hasColumn('production_scanned_data', 'store_checked_by')) $colsToDrop[] = 'store_checked_by';
                if (Schema::hasColumn('production_scanned_data', 'store_check_status')) $colsToDrop[] = 'store_check_status';

                if (! empty($colsToDrop)) {
                    $table->dropColumn($colsToDrop);
                }
            });
        }

        Schema::dropIfExists('store_crosscheck_daily_reports');
    }
};
