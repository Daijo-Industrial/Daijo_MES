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
        if (! Schema::hasTable('master_machines')) {
            Schema::create('master_machines', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->unique()->index();
                $table->string('plant', 20)->default('KBN')->index(); // 'KBN' or 'KARAWANG'
                $table->string('tonnage', 50)->nullable();
                $table->string('brand', 50)->nullable();
                $table->string('line_or_zone', 50)->nullable();
                $table->boolean('is_active')->default(true)->index(); // Check/uncheck to show on dashboard
                $table->integer('sort_order')->default(0);
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });

            // Seed initial data directly from users whose role is OPERATOR
            $now = now();
            $operatorUserNames = DB::table('users')
                ->join('roles', 'users.role_id', '=', 'roles.id')
                ->where('roles.name', 'OPERATOR')
                ->pluck('users.name');

            $records = [];
            foreach ($operatorUserNames as $name) {
                $cleanName = strtoupper(trim($name));
                if (empty($cleanName)) {
                    continue;
                }

                $isKarawang = str_starts_with($cleanName, 'K');
                $records[] = [
                    'name' => $cleanName,
                    'plant' => $isKarawang ? 'KARAWANG' : 'KBN',
                    'tonnage' => null,
                    'brand' => null,
                    'line_or_zone' => null,
                    'is_active' => true,
                    'sort_order' => 0,
                    'description' => 'Disinkronkan dari User Operator',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($records)) {
                DB::table('master_machines')->insertOrIgnore($records);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_machines');
    }
};
