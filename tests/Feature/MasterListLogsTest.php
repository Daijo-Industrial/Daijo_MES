<?php

namespace Tests\Feature;

use App\Livewire\Admin\MasterListLogs;
use App\Models\MasterBusinessPartner;
use App\Models\MasterItemImportBatch;
use App\Models\MasterItemLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterListLogsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_component_renders_and_defaults_to_updates_tab(): void
    {
        $this->actingAs($this->user);

        // Seed 1 create log and 1 update log
        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'ITEM-CREATE-01',
            'action' => 'excel_import_create',
            'old_values' => null,
            'new_values' => ['item_name' => 'Part A', 'customer_code' => '0'],
        ]);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'ITEM-UPDATE-01',
            'action' => 'excel_import_update',
            'old_values' => ['item_name' => 'Old Name'],
            'new_values' => ['item_name' => 'New Name'],
        ]);

        Livewire::test(MasterListLogs::class)
            ->assertSet('actionFilter', 'updates')
            ->assertSee('ITEM-UPDATE-01')
            ->assertDontSee('ITEM-CREATE-01')
            ->assertSee('Old Name')
            ->assertSee('New Name')
            ->assertSee('1 Kolom Diperbarui');
    }

    public function test_can_switch_to_creates_tab(): void
    {
        $this->actingAs($this->user);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'ITEM-CREATE-01',
            'action' => 'excel_import_create',
            'old_values' => null,
            'new_values' => ['item_name' => 'Initial Part'],
        ]);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'ITEM-UPDATE-01',
            'action' => 'inline_edit',
            'old_values' => ['item_name' => 'Before'],
            'new_values' => ['item_name' => 'After'],
        ]);

        Livewire::test(MasterListLogs::class)
            ->call('setActionFilter', 'creates')
            ->assertSet('actionFilter', 'creates')
            ->assertSee('ITEM-CREATE-01')
            ->assertSee('Item baru pertama kali didaftarkan')
            ->assertDontSee('ITEM-UPDATE-01');
    }

    public function test_can_switch_to_all_tab(): void
    {
        $this->actingAs($this->user);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'ITEM-CREATE-01',
            'action' => 'excel_import_create',
            'old_values' => null,
            'new_values' => ['item_name' => 'Initial Part'],
        ]);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'ITEM-UPDATE-01',
            'action' => 'inline_edit',
            'old_values' => ['item_name' => 'Before'],
            'new_values' => ['item_name' => 'After'],
        ]);

        Livewire::test(MasterListLogs::class)
            ->call('setActionFilter', 'all')
            ->assertSet('actionFilter', 'all')
            ->assertSee('ITEM-CREATE-01')
            ->assertSee('ITEM-UPDATE-01');
    }

    public function test_customer_code_resolves_to_partner_name_in_diff(): void
    {
        $this->actingAs($this->user);

        MasterBusinessPartner::create([
            'bp_code' => 'BP-TEST-001',
            'bp_name' => 'PT ASTRA DAIHATSU MOTOR',
            'type' => 'CUSTOMER',
        ]);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'ITEM-DIFF-CUST',
            'action' => 'inline_edit',
            'old_values' => ['customer_code' => '0'],
            'new_values' => ['customer_code' => 'BP-TEST-001'],
        ]);

        Livewire::test(MasterListLogs::class)
            ->assertSet('actionFilter', 'updates')
            ->assertSee('ITEM-DIFF-CUST')
            ->assertSee('0 (Unassigned / N/A)')
            ->assertSee('BP-TEST-001 (PT ASTRA DAIHATSU MOTOR)');
    }

    public function test_search_by_item_code_and_values(): void
    {
        $this->actingAs($this->user);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'SPECIFIC-ITEM-99',
            'action' => 'inline_edit',
            'old_values' => ['item_name' => 'Widget V1'],
            'new_values' => ['item_name' => 'Widget Super V2'],
        ]);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'OTHER-ITEM-11',
            'action' => 'inline_edit',
            'old_values' => ['item_name' => 'Gizmo A'],
            'new_values' => ['item_name' => 'Gizmo B'],
        ]);

        // Search by item code
        Livewire::test(MasterListLogs::class)
            ->set('search', 'SPECIFIC')
            ->assertSee('SPECIFIC-ITEM-99')
            ->assertDontSee('OTHER-ITEM-11');

        // Search by value inside new_values JSON
        Livewire::test(MasterListLogs::class)
            ->set('search', 'Super V2')
            ->assertSee('SPECIFIC-ITEM-99')
            ->assertDontSee('OTHER-ITEM-11');
    }

    public function test_tab_counts_are_calculated_accurately(): void
    {
        $this->actingAs($this->user);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'C-01',
            'action' => 'excel_import_create',
            'old_values' => null,
            'new_values' => ['item_name' => 'A'],
        ]);
        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'C-02',
            'action' => 'csv_import_create',
            'old_values' => null,
            'new_values' => ['item_name' => 'B'],
        ]);
        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'U-01',
            'action' => 'inline_edit',
            'old_values' => ['item_name' => 'Old'],
            'new_values' => ['item_name' => 'New'],
        ]);

        Livewire::test(MasterListLogs::class)
            ->assertViewHas('tabCounts', [
                'all' => 3,
                'updates' => 1,
                'creates' => 2,
            ]);
    }

    public function test_can_identify_problematic_upload_batch_and_isolate_its_changes(): void
    {
        $this->actingAs($this->user);

        // Batch 1: Safe upload (only creates)
        $batchSafe = MasterItemImportBatch::create([
            'batch_code' => 'BATCH-SAFE-01',
            'file_name' => 'new_parts_catalog.xls',
            'user_id' => $this->user->id,
            'total_rows' => 10,
            'created_count' => 10,
            'updated_count' => 0,
            'critical_changes_count' => 0,
        ]);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'PART-SAFE-01',
            'action' => 'excel_import_create',
            'upload_batch_id' => $batchSafe->batch_code,
            'file_name' => $batchSafe->file_name,
            'old_values' => null,
            'new_values' => ['item_name' => 'Safe Part'],
        ]);

        // Batch 2: Problematic upload (overwrote customer to 0 and changed weight)
        $batchProblem = MasterItemImportBatch::create([
            'batch_code' => 'BATCH-PROBLEM-02',
            'file_name' => 'problematic_sap_upload.xls',
            'user_id' => $this->user->id,
            'total_rows' => 5,
            'created_count' => 4,
            'updated_count' => 1,
            'critical_changes_count' => 1,
            'impact_summary' => ['customer_code' => 1, 'part_weight' => 1],
        ]);

        MasterItemLog::create([
            'user_id' => $this->user->id,
            'item_code' => 'PART-OVERWRITTEN',
            'action' => 'excel_import_update',
            'upload_batch_id' => $batchProblem->batch_code,
            'file_name' => $batchProblem->file_name,
            'old_values' => ['customer_code' => 'CUST-OK', 'part_weight' => 25.5],
            'new_values' => ['customer_code' => '0', 'part_weight' => 10.0],
        ]);

        // Test component displays problematic status in batch list
        $component = Livewire::test(MasterListLogs::class);
        $component->assertSee('problematic_sap_upload.xls')
            ->assertSee('1 Tertimpa')
            ->assertSee('new_parts_catalog.xls')
            ->assertSee('Aman (Baru)');

        // Select the problematic batch to investigate
        $component->call('selectBatch', $batchProblem->batch_code)
            ->assertSet('batchFilter', $batchProblem->batch_code)
            ->assertSet('actionFilter', 'updates')
            ->assertSee('MODE INVESTIGASI SESI UPLOAD')
            ->assertSee('problematic_sap_upload.xls')
            ->assertSee('Indikasi Masalah Terdeteksi')
            ->assertSee('PART-OVERWRITTEN')
            ->assertDontSee('PART-SAFE-01');

        // Scoped tab counts for the problematic batch
        $component->assertViewHas('tabCounts', [
            'all' => 1,
            'updates' => 1,
            'creates' => 0,
        ]);

        // Clear batch filter returns to global
        $component->call('clearBatchFilter')
            ->assertSet('batchFilter', '')
            ->assertDontSee('MODE INVESTIGASI SESI UPLOAD');
    }
}
