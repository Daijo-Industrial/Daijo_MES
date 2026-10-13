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
}
