<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_item_codes', function (Blueprint $table) {
            $table->index('user_id', 'daily_item_code_user_id_idx');
            $table->index('item_code', 'daily_item_code_item_code_idx');
            $table->index('schedule_date', 'daily_item_code_schedule_date_idx');

            $table->index(
                ['user_id', 'schedule_date', 'is_done'],
                'daily_item_code_user_schedule_done_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('daily_item_codes', function (Blueprint $table) {
            $table->dropIndex('daily_item_code_user_id_idx');
            $table->dropIndex('daily_item_code_item_code_idx');
            $table->dropIndex('daily_item_code_schedule_date_idx');
            $table->dropIndex('daily_item_code_user_schedule_done_idx');
        });
    }
};
