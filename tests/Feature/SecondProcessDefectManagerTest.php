<?php

namespace Tests\Feature;

use App\Livewire\SecondProcessDefectManager;
use App\Models\Role;
use App\Models\SecondProcessDefectAlias;
use App\Models\SecondProcessDefectCatalog;
use App\Models\SecondProcessNgRecord;
use App\Models\SecondProcessReport;
use App\Models\User;
use Database\Seeders\SecondProcessDefectCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SecondProcessDefectManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        return User::factory()->create([
            'role_id' => $role->id,
        ]);
    }

    protected function createReport(): SecondProcessReport
    {
        return SecondProcessReport::create([
            'date' => '2026-11-04',
            'unit_line' => 'Painting 1',
            'process_prod' => 'Painting',
            'shift' => '1',
            'status' => 'draft',
            'part_number' => 'PART-TEST-NG',
            'part_name' => 'Test Part NG',
            'customer' => 'Customer A',
            'target_per_hour' => 100,
            'jml_input_wip' => 100,
            'repairan' => 0,
            'jumlah_ok' => 90,
            'jumlah_ng' => 10,
            'jumlah_output' => 100,
            'ng_prosentase' => 10.0,
            'sisa_input' => 0,
            'created_by' => 'Tester',
        ]);
    }

    public function test_seeder_populates_canonical_catalogs_and_aliases(): void
    {
        $this->seed(SecondProcessDefectCatalogSeeder::class);

        $this->assertDatabaseHas('second_process_defect_catalogs', ['name' => 'SCRATCH']);
        $this->assertDatabaseHas('second_process_defect_catalogs', ['name' => 'HAIR MARK']);
        $this->assertDatabaseHas('second_process_defect_catalogs', ['name' => 'PEEL OFF']);
        $this->assertDatabaseHas('second_process_defect_catalogs', ['name' => 'ORANGE PEEL']);

        // Check aliases
        $this->assertDatabaseHas('second_process_defect_aliases', ['alias' => 'ORANGE SKIN']);
        $this->assertDatabaseHas('second_process_defect_aliases', ['alias' => 'FEEL OFF']);
        $this->assertDatabaseHas('second_process_defect_aliases', ['alias' => 'HAIRMARK']);
        $this->assertDatabaseHas('second_process_defect_aliases', ['alias' => 'SCRATH']);
    }

    public function test_resolve_canonical_helper_correctly_resolves_aliases(): void
    {
        $this->seed(SecondProcessDefectCatalogSeeder::class);

        // Alias resolves to parent canonical
        $this->assertSame('HAIR MARK', SecondProcessDefectAlias::resolveCanonical('HARI MARK'));
        $this->assertSame('HAIR MARK', SecondProcessDefectAlias::resolveCanonical('hairmark '));
        $this->assertSame('PEEL OFF', SecondProcessDefectAlias::resolveCanonical('FEEL OFF'));
        $this->assertSame('PEEL OFF', SecondProcessDefectAlias::resolveCanonical('fell off'));
        $this->assertSame('ORANGE PEEL', SecondProcessDefectAlias::resolveCanonical('ORANGE SKIN'));
        $this->assertSame('SCRATCH', SecondProcessDefectAlias::resolveCanonical('SCRATH'));

        // Already canonical remains canonical
        $this->assertSame('SCRATCH', SecondProcessDefectAlias::resolveCanonical('SCRATCH'));

        // Unknown text uppercase fallback
        $this->assertSame('UNKNOWN DEFECT', SecondProcessDefectAlias::resolveCanonical('unknown defect'));
        $this->assertNull(SecondProcessDefectAlias::resolveCanonical(''));
    }

    public function test_artisan_command_standardizes_ng_names_in_database(): void
    {
        $this->seed(SecondProcessDefectCatalogSeeder::class);
        $report = $this->createReport();

        $rec1 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_name' => 'HARI MARK', // Alias
            'total_ng' => 5,
        ]);

        $rec2 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_name' => 'ORANGE SKIN', // Alias
            'total_ng' => 3,
        ]);

        $rec3 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_name' => 'SCRATCH', // Already standard
            'total_ng' => 2,
        ]);

        // 1. Dry run should NOT modify DB
        $this->artisan('second-process:standardize-ng-names', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY-RUN]')
            ->assertExitCode(0);

        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec1->id,
            'ng_name' => 'HARI MARK',
        ]);

        // 2. Cancellation should abort
        $this->artisan('second-process:standardize-ng-names')
            ->expectsConfirmation('Are you sure you want to update 2 records in the database?', 'no')
            ->expectsOutputToContain('Operation aborted by user')
            ->assertExitCode(1);

        // 3. Execution with --force should update
        $this->artisan('second-process:standardize-ng-names', ['--force' => true])
            ->expectsOutputToContain('Successfully standardized 2 records in second_process_ng_records')
            ->assertExitCode(0);

        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec1->id,
            'ng_name' => 'HAIR MARK',
        ]);

        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec2->id,
            'ng_name' => 'ORANGE PEEL',
        ]);

        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec3->id,
            'ng_name' => 'SCRATCH',
        ]);

        // 4. Idempotency test
        $this->artisan('second-process:standardize-ng-names', ['--force' => true])
            ->expectsOutputToContain('All second_process_ng_records are already standardized')
            ->assertExitCode(0);
    }

    public function test_controller_auto_resolves_aliases_when_saving_report(): void
    {
        $this->seed(SecondProcessDefectCatalogSeeder::class);
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->post(route('second-process-reports.store'), [
            'date' => '2026-11-04',
            'unit_line' => 'Painting 1',
            'shift' => '1',
            'process_prod' => 'Painting',
            'status' => 'draft',
            'part_number' => 'PART-TEST-STORE',
            'part_name' => 'Test Part Store',
            'customer' => 'N/A',
            'target_per_hour' => 100,
            'jml_input_wip' => 10,
            'repairan' => 0,
            'jml_ng_lebur' => 0,
            'ngs' => [
                [
                    'ng_name' => 'ORANGE SKIN', // Should auto-resolve to ORANGE PEEL
                    'hours' => [1, 0, 0],
                    'total_ng' => 1,
                    'ng_category' => 'NG-PROSES',
                ],
                [
                    'ng_name' => 'FEEL OFF', // Should auto-resolve to PEEL OFF
                    'hours' => [2, 0, 0],
                    'total_ng' => 2,
                    'ng_category' => 'NG-PROSES',
                ],
            ],
        ]);

        $response->assertRedirect();

        // Verify resolved in database
        $this->assertDatabaseHas('second_process_ng_records', [
            'ng_name' => 'ORANGE PEEL',
        ]);
        $this->assertDatabaseHas('second_process_ng_records', [
            'ng_name' => 'PEEL OFF',
        ]);
        $this->assertDatabaseMissing('second_process_ng_records', [
            'ng_name' => 'ORANGE SKIN',
        ]);
    }

    public function test_livewire_defect_manager_crud_and_unmapped_triage(): void
    {
        $this->seed(SecondProcessDefectCatalogSeeder::class);
        $user = $this->createAdminUser();
        $report = $this->createReport();

        // Create an unmapped defect record
        SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_name' => 'RANDOM UNMAPPED DEFECT',
            'total_ng' => 7,
        ]);

        Livewire::actingAs($user)
            ->test(SecondProcessDefectManager::class)
            // 1. Triage queue should detect unmapped defect
            ->assertSee('RANDOM UNMAPPED DEFECT')
            // 2. Can create a new catalog
            ->set('newCatalogName', 'CUSTOM NEW DEFECT')
            ->set('newCatalogDescription', 'Test Description')
            ->call('createCatalog')
            ->assertHasNoErrors()
            // 3. Can add alias
            ->set('selectedCatalogId', SecondProcessDefectCatalog::where('name', 'CUSTOM NEW DEFECT')->first()->id)
            ->set('newAliasInput', 'CUSTOM ALIAS')
            ->call('addAlias')
            ->assertHasNoErrors()
            // 4. Can assign unmapped defect to Scratch
            ->call('assignUnmappedToCatalog', 'RANDOM UNMAPPED DEFECT', SecondProcessDefectCatalog::where('name', 'SCRATCH')->first()->id)
            // 5. Standardize backlog button
            ->call('standardizeBacklog');

        $this->assertDatabaseHas('second_process_defect_catalogs', [
            'name' => 'CUSTOM NEW DEFECT',
        ]);

        $this->assertDatabaseHas('second_process_defect_aliases', [
            'alias' => 'CUSTOM ALIAS',
        ]);

        // Unmapped record should have been standardized to SCRATCH
        $this->assertDatabaseHas('second_process_ng_records', [
            'ng_name' => 'SCRATCH',
            'total_ng' => 7,
        ]);
    }
}
