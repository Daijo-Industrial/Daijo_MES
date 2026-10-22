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
        if (Schema::hasTable('master_boms')) {
            Schema::table('master_boms', function (Blueprint $table) {
                if (!Schema::hasColumn('master_boms', 'family')) {
                    $table->string('family', 150)->nullable()->after('uom')->comment('Family label dari Excel SAP (Prioritas Kolom 1)');
                }
                if (!Schema::hasColumn('master_boms', 'family_2')) {
                    $table->string('family_2', 150)->nullable()->after('family')->comment('Family label cadangan dari Kolom 2 Excel');
                }
            });
        }

        if (Schema::hasTable('master_bom_fg_headers')) {
            Schema::table('master_bom_fg_headers', function (Blueprint $table) {
                if (!Schema::hasColumn('master_bom_fg_headers', 'family')) {
                    $table->string('family', 150)->nullable()->after('project_code')->comment('Family label dari Master BOM SAP');
                    $table->index('family');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('master_boms')) {
            Schema::table('master_boms', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('master_boms', 'family_2')) $cols[] = 'family_2';
                if (Schema::hasColumn('master_boms', 'family')) $cols[] = 'family';
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('master_bom_fg_headers')) {
            Schema::table('master_bom_fg_headers', function (Blueprint $table) {
                if (Schema::hasColumn('master_bom_fg_headers', 'family')) {
                    $table->dropIndex(['family']);
                    $table->dropColumn('family');
                }
            });
        }
    }
};
