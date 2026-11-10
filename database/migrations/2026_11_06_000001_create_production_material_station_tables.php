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
        // 1. Tambahkan kolom status verifikasi penerimaan di mwh_outgoings
        if (Schema::hasTable('mwh_outgoings')) {
            Schema::table('mwh_outgoings', function (Blueprint $table) {
                if (! Schema::hasColumn('mwh_outgoings', 'received_status')) {
                    $table->string('received_status', 30)->default('PENDING')->index()->after('remarks');
                }
                if (! Schema::hasColumn('mwh_outgoings', 'received_by')) {
                    $table->foreignId('received_by')->nullable()->after('received_status')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('mwh_outgoings', 'received_by_name')) {
                    $table->string('received_by_name', 100)->nullable()->after('received_by');
                }
                if (! Schema::hasColumn('mwh_outgoings', 'received_at')) {
                    $table->dateTime('received_at')->nullable()->after('received_by_name');
                }
                if (! Schema::hasColumn('mwh_outgoings', 'received_notes')) {
                    $table->text('received_notes')->nullable()->after('received_at');
                }
            });
        }

        // 2. Buat tabel pencatatan pemakaian material di lantai produksi (production_material_usages)
        if (! Schema::hasTable('production_material_usages')) {
            Schema::create('production_material_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('outgoing_id')->nullable()->constrained('mwh_outgoings')->nullOnDelete();
                $table->string('item_code', 100)->index();
                $table->string('item_name')->nullable();
                $table->string('pallet_id', 100)->nullable()->index();
                $table->string('machine_no', 50)->nullable()->index();
                $table->string('spk_code', 50)->nullable()->index();
                $table->decimal('qty_used', 12, 2)->default(0);
                $table->string('uom', 20)->default('KG');
                $table->string('purpose', 50)->default('PRODUKSI')->index(); // PRODUKSI, PURGING, TRIAL, REJECT
                $table->dateTime('used_at')->index();
                $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('used_by_name', 100)->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_material_usages');

        if (Schema::hasTable('mwh_outgoings')) {
            Schema::table('mwh_outgoings', function (Blueprint $table) {
                $columns = ['received_notes', 'received_at', 'received_by_name', 'received_by', 'received_status'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('mwh_outgoings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

