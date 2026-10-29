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

    public function test_dashboard_respects_checked_and_unchecked_machines_from_master_machines(): void
    {
        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $admin = User::factory()->create([
            'role_id' => $role->id,
        ]);

        // Pastikan ada machine user di tabel users
        User::firstOrCreate(
            ['name' => '0450H'],
            ['email' => '0450h@daijo.co.id', 'password' => bcrypt('password'), 'role_id' => $role->id]
        );
        User::firstOrCreate(
            ['name' => 'K1400C'],
            ['email' => 'k1400c@daijo.co.id', 'password' => bcrypt('password'), 'role_id' => $role->id]
        );

        // Uncheck / nonaktifkan 0450H
        \App\Models\MasterMachine::where('name', '0450H')->update(['is_active' => false]);
        \App\Models\MasterMachine::where('name', 'K1400C')->update(['is_active' => true]);

        $response = $this->actingAs($admin)->get(route('djoni.dashboard'));
        $response->assertStatus(200);

        // K1400C (checked) harus tampil
        $response->assertSee('<option value="K1400C"', false);
        // 0450H (unchecked) TIDAK BOLEH tampil di dropdown
        $response->assertDontSee('<option value="0450H"', false);
    }

    public function test_master_machine_manager_toggles_machine_visibility(): void
    {
        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $admin = User::factory()->create([
            'role_id' => $role->id,
        ]);

        $machine = \App\Models\MasterMachine::firstOrCreate(
            ['name' => '0350F'],
            ['plant' => 'KBN', 'is_active' => true]
        );

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\MasterMachineManager::class)
            ->assertSee('0350F')
            ->call('toggleActive', $machine->id)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'info');

        $this->assertFalse($machine->fresh()->is_active);

        // Toggle back to active
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\MasterMachineManager::class)
            ->call('toggleActive', $machine->id)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'success');

        $this->assertTrue($machine->fresh()->is_active);
    }

    public function test_master_machine_manager_can_create_new_machine_and_sync_user(): void
    {
        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $admin = User::factory()->create([
            'role_id' => $role->id,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\MasterMachineManager::class)
            ->call('openCreateModal')
            ->set('name', 'K1500Z')
            ->assertSet('plant', 'KARAWANG') // Auto-detected from prefix 'K'
            ->set('tonnage', '1500 Ton')
            ->set('brand', 'JSW')
            ->set('is_active', true)
            ->call('saveMachine')
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'success');

        $this->assertDatabaseHas('master_machines', [
            'name' => 'K1500Z',
            'plant' => 'KARAWANG',
            'tonnage' => '1500 Ton',
            'brand' => 'JSW',
            'is_active' => true,
        ]);

        // Akun user mesin otomatis terbuat
        $this->assertDatabaseHas('users', [
            'name' => 'K1500Z',
        ]);
    }

    public function test_master_machine_manager_syncs_from_operator_users(): void
    {
        $role = Role::firstOrCreate(['name' => 'OPERATOR']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
        ]);

        // Buat user mesin dengan role OPERATOR
        User::firstOrCreate(
            ['name' => '0990A'],
            [
                'email' => '0990a@daijo.co.id',
                'password' => bcrypt('password'),
                'role_id' => $role->id,
            ]
        );

        // Hapus dari master_machines jika sempat ada (bersihkan total)
        \App\Models\MasterMachine::withTrashed()->where('name', '0990A')->forceDelete();

        // Panggil Livewire MasterMachineManager (otomatis sync di mount atau via tombol)
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\MasterMachineManager::class)
            ->call('syncFromOperatorUsers')
            ->assertSee('0990A');

        $this->assertDatabaseHas('master_machines', [
            'name' => '0990A',
            'plant' => 'KBN',
            'is_active' => true,
        ]);
    }

    public function test_deleting_machine_does_not_crash_on_sync_or_page_reload(): void
    {
        $role = Role::firstOrCreate(['name' => 'OPERATOR']);
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
        ]);

        // Buat akun user operator AMANDA
        User::firstOrCreate(
            ['name' => 'AMANDA'],
            [
                'email' => 'amanda@daijo.co.id',
                'password' => bcrypt('password'),
                'role_id' => $role->id,
            ]
        );

        // Buat mesin AMANDA di master_machines
        $machine = \App\Models\MasterMachine::firstOrCreate(
            ['name' => 'AMANDA'],
            ['plant' => 'KBN', 'is_active' => true]
        );

        // Delete mesin AMANDA melalui Livewire
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\MasterMachineManager::class)
            ->call('deleteMachine', $machine->id)
            ->assertDispatched('notify', fn ($name, $params) => $params['type'] === 'info');

        // Pastikan mesin terhapus secara soft delete
        $this->assertSoftDeleted('master_machines', ['name' => 'AMANDA']);

        // Buka kembali halaman MasterMachineManager (mount) atau panggil sync
        // TIDAK BOLEH memicu SQLSTATE[23000] Duplicate entry error
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\MasterMachineManager::class)
            ->call('syncFromOperatorUsers')
            ->assertDontSeeHtml('AMANDA'); // AMANDA tetap tersembunyi karena sudah dihapus
    }
}

