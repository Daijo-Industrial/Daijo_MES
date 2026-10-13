<?php

namespace Tests\Feature;

use App\Models\MachineJob;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncMachineJobsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_machine_jobs_creates_missing_jobs_for_role_4(): void
    {
        // Setup role ID 4
        $role4 = Role::firstOrCreate(['id' => 4], ['name' => 'OPERATOR']);

        // Create 2 users with role_id 4
        $user1 = User::factory()->create([
            'role_id' => 4,
            'name' => '0300A',
        ]);
        $user2 = User::factory()->create([
            'role_id' => 4,
            'name' => '0350F',
        ]);

        // User 2 already has a machine job
        MachineJob::create(['user_id' => $user2->id]);

        // User 3 has a different role (e.g. 1)
        $role1 = Role::firstOrCreate(['id' => 1], ['name' => 'SUPER-ADMIN']);
        $user3 = User::factory()->create([
            'role_id' => 1,
            'name' => 'admin_user',
        ]);

        $this->assertDatabaseMissing('machine_jobs', ['user_id' => $user1->id]);
        $this->assertDatabaseHas('machine_jobs', ['user_id' => $user2->id]);
        $this->assertDatabaseMissing('machine_jobs', ['user_id' => $user3->id]);

        // Run command
        $this->artisan('machine:sync-jobs')
            ->expectsOutputToContain('Ditemukan 1 user (role_id 4) yang belum memiliki machine_jobs')
            ->expectsOutputToContain('Berhasil membuat 1 record machine_jobs baru!')
            ->assertExitCode(0);

        // Assert user1 now has a machine job
        $this->assertDatabaseHas('machine_jobs', ['user_id' => $user1->id]);
        // User3 still does NOT have a machine job
        $this->assertDatabaseMissing('machine_jobs', ['user_id' => $user3->id]);
    }

    public function test_sync_machine_jobs_dry_run_does_not_create_records(): void
    {
        Role::firstOrCreate(['id' => 4], ['name' => 'OPERATOR']);

        $user = User::factory()->create([
            'role_id' => 4,
            'name' => '0300A',
        ]);

        $this->artisan('machine:sync-jobs --dry-run')
            ->expectsOutputToContain('Mode --dry-run aktif')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('machine_jobs', ['user_id' => $user->id]);
    }

    public function test_sync_machine_jobs_when_all_users_already_have_jobs(): void
    {
        Role::firstOrCreate(['id' => 4], ['name' => 'OPERATOR']);

        $user = User::factory()->create([
            'role_id' => 4,
            'name' => '0300A',
        ]);
        MachineJob::create(['user_id' => $user->id]);

        $this->artisan('machine:sync-jobs')
            ->expectsOutputToContain('Semua user dengan role_id 4 sudah memiliki machine_jobs')
            ->assertExitCode(0);
    }
}
