<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionDayDashboardMachineListTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_day_dashboard_contains_all_specified_machines(): void
    {
        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $machinesToTest = [
            '0350F', '0450F', '0450G', '0450H', 'K1400C',
            'K0750B', 'K1400B', 'K1400A', '0450I', 'K0900A',
            '0450J', '0550B', '0650D', 'K0450A', 'K0650B',
            '0360A', '0360D', '0150E', '0450B', '0650E',
            '0300A', 'K0750A', 'K0650A',
            '0300B', '0360B', '0360C', '0150F', '1300B', '1300C', '1050D',
        ];

        foreach ($machinesToTest as $name) {
            User::firstOrCreate(
                ['name' => $name],
                [
                    'email' => strtolower($name) . '@daijo.co.id',
                    'password' => bcrypt('password'),
                    'role_id' => $role->id,
                ]
            );
        }

        $response = $this->actingAs($user)->get(route('djoni.dashboard'));
        $response->assertStatus(200);

        foreach ($machinesToTest as $name) {
            $response->assertSee('<option value="' . $name . '"', false);
        }
    }

    public function test_production_day_dashboard_renders_machine_card_even_if_machine_job_was_missing(): void
    {
        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $admin = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $machineUser = User::firstOrCreate(
            ['name' => '0300A'],
            [
                'email' => '0300a@daijo.co.id',
                'password' => bcrypt('password'),
                'role_id' => 4,
            ]
        );

        $response = $this->actingAs($admin)->get(route('djoni.dashboard', [
            'machine_name' => '0300A',
            'date' => '2026-10-12',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Machine: 0300A');
        $this->assertDatabaseHas('machine_jobs', ['user_id' => $machineUser->id]);
    }

    public function test_production_day_dashboard_renders_newly_added_machines(): void
    {
        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $admin = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $machine1300B = User::firstOrCreate(
            ['name' => '1300B'],
            [
                'email' => '1300b@daijo.co.id',
                'password' => bcrypt('password'),
                'role_id' => 4,
            ]
        );

        $response = $this->actingAs($admin)->get(route('djoni.dashboard', [
            'machine_name' => '1300B',
            'date' => '2026-10-12',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Machine: 1300B');
        $this->assertDatabaseHas('machine_jobs', ['user_id' => $machine1300B->id]);
    }
}
