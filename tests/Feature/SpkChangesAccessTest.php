<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SpkChangesAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->createTestSchema();
    }

    private function createTestSchema(): void
    {
        Schema::create('branches', function ($table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('role_id')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('spk_masters', function ($table) {
            $table->id();
            $table->string('spk_number')->unique();
            $table->string('item_code');
            $table->decimal('planned_quantity', 12, 2)->default(0);
            $table->decimal('completed_quantity', 12, 2)->default(0);
            $table->string('production_status')->default('PLANNED');
            $table->timestamps();
        });

        Schema::create('spk_change_logs', function ($table) {
            $table->id();
            $table->string('sync_batch_id')->nullable();
            $table->string('spk_number');
            $table->string('item_code');
            $table->string('change_type');
            $table->decimal('old_planned_qty', 12, 2)->nullable();
            $table->decimal('new_planned_qty', 12, 2)->nullable();
            $table->decimal('old_completed_qty', 12, 2)->nullable();
            $table->decimal('new_completed_qty', 12, 2)->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('api_logs', function ($table) {
            $table->id();
            $table->string('api_name');
            $table->string('method');
            $table->string('endpoint');
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('status')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/spk-changes');
        $response->assertRedirect('/login');
    }

    public function test_non_production_role_gets_forbidden(): void
    {
        $role = Role::create(['name' => 'WORKSHOP']);
        $user = User::create([
            'name' => 'Workshop User',
            'email' => 'workshop@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($user)->get('/spk-changes');
        $response->assertForbidden();
    }

    public function test_production_role_can_access_spk_changes(): void
    {
        $role = Role::create(['name' => 'PRODUCTION']);
        $user = User::create([
            'name' => 'Production User',
            'email' => 'prod@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $response = $this->actingAs($user)->get('/spk-changes');
        $response->assertOk();
        $response->assertSee('Log Perubahan SPK');
    }
}
