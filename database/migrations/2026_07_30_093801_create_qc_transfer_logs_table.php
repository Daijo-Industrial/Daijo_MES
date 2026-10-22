<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_transfer_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_summary_id');
            $table->unsignedBigInteger('scanned_data_id');
            $table->string('item_code');
            $table->string('spk_code');
            $table->string('label')->nullable();
            $table->string('from_warehouse');          // FFI / KRFFI
            $table->integer('original_qty');            // qty asli box
            $table->integer('ok_qty');                  // qty OK = original - ng
            $table->integer('ng_qty');                  // qty NG (input QC)

            // Transfer OK (FFI→FG / KRFFI→KRFG)
            $table->string('ok_to_warehouse');          // FG / KRFG
            $table->tinyInteger('ok_sap_status')->default(0); // 0=pending,1=success,2=failed,3=processing
            $table->text('ok_sap_error')->nullable();
            $table->datetime('ok_sap_sent_at')->nullable();

            // Transfer NG (FFI→RJCT / KRFFI→KRRJCT)
            $table->string('ng_to_warehouse')->nullable();    // RJCT / KRRJCT (null jika ng=0)
            $table->tinyInteger('ng_sap_status')->default(0);
            $table->text('ng_sap_error')->nullable();
            $table->datetime('ng_sap_sent_at')->nullable();

            $table->unsignedBigInteger('inspected_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('production_summary_id');
            $table->index('scanned_data_id');
            $table->index(['ok_sap_status', 'ng_sap_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_transfer_logs');
    }
};
