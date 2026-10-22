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
        Schema::table('master_business_partners', function (Blueprint $table) {
            $table->string('industry', 50)->default('GENERAL')->after('category')->index();
        });

        // Smart auto-tagging of existing business partners:
        // - Moulding: type or foreign_name has value like "mould" or "moulding" (or "mold")
        // - Automotive: sales employee Andriani (Ext 131) or Anik (Ext 155)
        // - Electronics: name / alias matches electronics keywords
        // - General: rest
        $electronicsKeywords = [
            'sharp', 'toshiba', 'sanken', 'panasonic', 'hartono istana', 'hit',
            'bumjin', 'samsung', 'lg', 'electra', 'sony', 'electronic',
        ];

        \Illuminate\Support\Facades\DB::table('master_business_partners')->orderBy('id')->chunk(200, function ($bps) use ($electronicsKeywords) {
            foreach ($bps as $bp) {
                $type = strtolower($bp->type ?? '');
                $foreignName = strtolower($bp->foreign_name ?? '');
                $sales = strtolower($bp->sales_employee ?? '');
                $industry = 'GENERAL';

                if (
                    str_contains($type, 'mould') || str_contains($type, 'mold') ||
                    str_contains($foreignName, 'mould') || str_contains($foreignName, 'mold')
                ) {
                    $industry = 'MOULDING';
                } elseif (str_contains($sales, 'andriani') || str_contains($sales, 'anik')) {
                    $industry = 'AUTOMOTIVE';
                } else {
                    $text = strtolower(($bp->bp_name ?? '') . ' ' . ($bp->foreign_name ?? ''));
                    foreach ($electronicsKeywords as $kw) {
                        if (str_contains($text, $kw)) {
                            $industry = 'ELECTRONICS';
                            break;
                        }
                    }
                }

                if ($industry !== 'GENERAL') {
                    \Illuminate\Support\Facades\DB::table('master_business_partners')
                        ->where('id', $bp->id)
                        ->update(['industry' => $industry]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_business_partners', function (Blueprint $table) {
            $table->dropColumn('industry');
        });
    }
};
