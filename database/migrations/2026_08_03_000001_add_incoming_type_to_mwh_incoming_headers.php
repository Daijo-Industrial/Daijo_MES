<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mwh_incoming_headers', function (Blueprint $table) {
            $table->string('incoming_type', 50)->default('SUPPLIER')->after('document_no');
            $table->string('returned_from', 255)->nullable()->after('supplier_name');
            $table->string('original_outgoing_code', 100)->nullable()->after('po_number');
        });
    }

    public function down(): void
    {
        Schema::table('mwh_incoming_headers', function (Blueprint $table) {
            $table->dropColumn(['incoming_type', 'returned_from', 'original_outgoing_code']);
        });
    }
};
