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
        // 1. Add unique constraint to roles.name to prevent duplicate roles
        Schema::table('roles', function (Blueprint $table) {
            $table->string('name')->unique()->change();
        });

        // 2. Add columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role_id');
            $table->softDeletes()->after('updated_at');
        });

        // 3. Insert SUPER-ADMIN role if not exists
        if (! \DB::table('roles')->where('name', 'SUPER-ADMIN')->exists()) {
            \DB::table('roles')->insert([
                'name' => 'SUPER-ADMIN',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'deleted_at']);
        });

        // Remove unique constraint from roles.name
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });

        // Delete SUPER-ADMIN role
        \DB::table('roles')->where('name', 'SUPER-ADMIN')->delete();
    }
};
