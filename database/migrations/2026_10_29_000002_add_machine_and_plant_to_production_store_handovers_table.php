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
        Schema::table('production_store_handovers', function (Blueprint $table) {
            // Drop old unique constraint
            $table->dropUnique('prod_store_handover_unique');

            // Add machine name and plant columns
            $table->string('machine_name', 50)->default('UNKNOWN')->after('shift')->index();
            $table->string('plant', 20)->default('KBN')->after('machine_name')->index();

            // Re-create composite unique constraint scoped per machine
            $table->unique(['production_date', 'shift', 'item_code', 'machine_name'], 'prod_store_handover_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_store_handovers', function (Blueprint $table) {
            $table->dropUnique('prod_store_handover_unique');
            $table->dropColumn(['machine_name', 'plant']);
            $table->unique(['production_date', 'shift', 'item_code'], 'prod_store_handover_unique');
        });
    }
};
