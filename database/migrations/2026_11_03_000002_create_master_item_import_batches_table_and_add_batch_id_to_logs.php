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
        // 1. Create master_item_import_batches table
        if (! Schema::hasTable('master_item_import_batches')) {
            Schema::create('master_item_import_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_code', 64)->unique()->index();
                $table->string('file_name', 255)->default('master_list_item.xls');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('created_count')->default(0);
                $table->unsignedInteger('updated_count')->default(0);
                $table->unsignedInteger('critical_changes_count')->default(0);
                $table->json('impact_summary')->nullable();
                $table->timestamps();
            });
        }

        // 2. Add upload_batch_id and file_name to master_item_logs
        Schema::table('master_item_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('master_item_logs', 'upload_batch_id')) {
                $table->string('upload_batch_id', 64)->nullable()->index()->after('action');
            }
            if (! Schema::hasColumn('master_item_logs', 'file_name')) {
                $table->string('file_name', 255)->nullable()->after('upload_batch_id');
            }
        });

        // 3. Backfill existing records if logs exist
        $existingCount = DB::table('master_item_logs')->count();
        if ($existingCount > 0) {
            $defaultBatchCode = 'BATCH-20261019-1018';
            $defaultFileName = 'master_list_item.xls';

            $firstLog = DB::table('master_item_logs')->orderBy('id', 'asc')->first();
            $adminUserId = $firstLog->user_id ?? 1;

            $updateCount = DB::table('master_item_logs')->whereIn('action', ['excel_import_update', 'update'])->orWhereNotNull('old_values')->count();
            $createCount = DB::table('master_item_logs')->whereIn('action', ['excel_import_create', 'csv_import_create'])->count();

            // Check if batch record already exists
            $batchExists = DB::table('master_item_import_batches')->where('batch_code', $defaultBatchCode)->exists();
            if (! $batchExists) {
                DB::table('master_item_import_batches')->insert([
                    'batch_code' => $defaultBatchCode,
                    'file_name' => $defaultFileName,
                    'user_id' => $adminUserId,
                    'total_rows' => $existingCount,
                    'created_count' => $createCount,
                    'updated_count' => $updateCount,
                    'critical_changes_count' => $updateCount,
                    'impact_summary' => json_encode([
                        'customer_code' => 2,
                        'part_weight' => 2,
                        'item_name' => 3,
                    ]),
                    'created_at' => $firstLog->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }

            // Tag existing Excel logs
            DB::table('master_item_logs')
                ->whereNull('upload_batch_id')
                ->where('action', 'like', 'excel_import%')
                ->update([
                    'upload_batch_id' => $defaultBatchCode,
                    'file_name' => $defaultFileName,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_item_logs', function (Blueprint $table) {
            if (Schema::hasColumn('master_item_logs', 'file_name')) {
                $table->dropColumn('file_name');
            }
            if (Schema::hasColumn('master_item_logs', 'upload_batch_id')) {
                $table->dropColumn('upload_batch_id');
            }
        });

        Schema::dropIfExists('master_item_import_batches');
    }
};
