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
        Schema::table('hourly_remarks', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $sm->listTableIndexes('hourly_remarks');

            if (! array_key_exists('hourly_remarks_dic_id_idx', $indexes)) {
                $table->index('dic_id', 'hourly_remarks_dic_id_idx');
            }
            if (! array_key_exists('hourly_remarks_created_at_idx', $indexes)) {
                $table->index('created_at', 'hourly_remarks_created_at_idx');
            }
            if (! array_key_exists('hourly_remarks_pic_idx', $indexes)) {
                $table->index('pic', 'hourly_remarks_pic_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hourly_remarks', function (Blueprint $table) {
            $table->dropIndex('hourly_remarks_dic_id_idx');
            $table->dropIndex('hourly_remarks_created_at_idx');
            $table->dropIndex('hourly_remarks_pic_idx');
        });
    }
};
