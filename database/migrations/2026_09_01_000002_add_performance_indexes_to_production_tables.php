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
        // 1. Index daily_item_codes table
        if (Schema::hasTable('daily_item_codes')) {
            Schema::table('daily_item_codes', function (Blueprint $table) {
                if (Schema::hasColumn('daily_item_codes', 'start_date')) {
                    $table->index('start_date', 'dic_start_date_idx');
                    $table->index(['start_date', 'user_id'], 'dic_start_date_user_idx');
                    $table->index(['start_date', 'item_code'], 'dic_start_date_item_idx');
                    $table->index(['start_date', 'shift'], 'dic_start_date_shift_idx');
                }
            });
        }

        // 2. Index production_ng_details table
        if (Schema::hasTable('production_ng_details')) {
            Schema::table('production_ng_details', function (Blueprint $table) {
                if (Schema::hasColumn('production_ng_details', 'hourly_remark_id')) {
                    $table->index('hourly_remark_id', 'ng_details_hourly_remark_id_idx');
                }
                if (Schema::hasColumn('production_ng_details', 'ng_type_id')) {
                    $table->index('ng_type_id', 'ng_details_ng_type_id_idx');
                }
            });
        }

        // 3. Index adjust_machine_logs table
        if (Schema::hasTable('adjust_machine_logs')) {
            Schema::table('adjust_machine_logs', function (Blueprint $table) {
                if (Schema::hasColumn('adjust_machine_logs', 'created_at')) {
                    $table->index('created_at', 'adjust_logs_created_at_idx');
                }
                if (Schema::hasColumn('adjust_machine_logs', 'user_id')) {
                    $table->index('user_id', 'adjust_logs_user_id_idx');
                }
                if (Schema::hasColumn('adjust_machine_logs', 'item_code')) {
                    $table->index('item_code', 'adjust_logs_item_code_idx');
                }
                if (Schema::hasColumn('adjust_machine_logs', 'pic')) {
                    $table->index('pic', 'adjust_logs_pic_idx');
                }
            });
        }

        // 4. Index mould_change_logs table
        if (Schema::hasTable('mould_change_logs')) {
            Schema::table('mould_change_logs', function (Blueprint $table) {
                if (Schema::hasColumn('mould_change_logs', 'created_at')) {
                    $table->index('created_at', 'mould_logs_created_at_idx');
                }
                if (Schema::hasColumn('mould_change_logs', 'user_id')) {
                    $table->index('user_id', 'mould_logs_user_id_idx');
                }
                if (Schema::hasColumn('mould_change_logs', 'item_code')) {
                    $table->index('item_code', 'mould_logs_item_code_idx');
                }
                if (Schema::hasColumn('mould_change_logs', 'pic')) {
                    $table->index('pic', 'mould_logs_pic_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('daily_item_codes')) {
            Schema::table('daily_item_codes', function (Blueprint $table) {
                $table->dropIndex('dic_start_date_idx');
                $table->dropIndex('dic_start_date_user_idx');
                $table->dropIndex('dic_start_date_item_idx');
                $table->dropIndex('dic_start_date_shift_idx');
            });
        }

        if (Schema::hasTable('production_ng_details')) {
            Schema::table('production_ng_details', function (Blueprint $table) {
                $table->dropIndex('ng_details_hourly_remark_id_idx');
                $table->dropIndex('ng_details_ng_type_id_idx');
            });
        }

        if (Schema::hasTable('adjust_machine_logs')) {
            Schema::table('adjust_machine_logs', function (Blueprint $table) {
                $table->dropIndex('adjust_logs_created_at_idx');
                $table->dropIndex('adjust_logs_user_id_idx');
                $table->dropIndex('adjust_logs_item_code_idx');
                $table->dropIndex('adjust_logs_pic_idx');
            });
        }

        if (Schema::hasTable('mould_change_logs')) {
            Schema::table('mould_change_logs', function (Blueprint $table) {
                $table->dropIndex('mould_logs_created_at_idx');
                $table->dropIndex('mould_logs_user_id_idx');
                $table->dropIndex('mould_logs_item_code_idx');
                $table->dropIndex('mould_logs_pic_idx');
            });
        }
    }
};
