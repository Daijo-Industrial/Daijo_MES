<?php

namespace Tests\Feature;

use App\Models\SecondProcessNgRecord;
use App\Models\SecondProcessReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StandardizeSecondProcessNgRecordsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function createReport(): SecondProcessReport
    {
        return SecondProcessReport::create([
            'date' => '2026-11-04',
            'unit_line' => 'Painting 1',
            'process_prod' => 'Painting',
            'shift' => '1',
            'status' => 'draft',
            'part_number' => 'PART-TEST-001',
            'part_name' => 'Test Part',
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

    public function test_dry_run_previews_changes_without_modifying_database(): void
    {
        $report = $this->createReport();

        $record1 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => 'NG-PROCESS',
            'ng_name' => 'DIRTY',
            'total_ng' => 5,
            'ng_input_item' => '[5] NG-PROCESS',
            'ng_input_qty' => 5,
        ]);

        $record2 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => null,
            'ng_name' => 'SCRATCH',
            'total_ng' => 3,
            'ng_input_item' => '[3] NG-PROSES',
            'ng_input_qty' => 3,
        ]);

        $this->artisan('second-process:standardize-ng-records', ['--dry-run' => true])
            ->expectsOutputToContain('No changes were written to the database')
            ->assertExitCode(0);

        // Verify database is untouched
        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $record1->id,
            'ng_category' => 'NG-PROCESS',
            'ng_input_item' => '[5] NG-PROCESS',
        ]);

        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $record2->id,
            'ng_category' => null,
            'ng_input_item' => '[3] NG-PROSES',
        ]);
    }

    public function test_cancelling_confirmation_aborts_without_modifying_database(): void
    {
        $report = $this->createReport();

        $record = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => 'NG-PROCESS',
            'ng_name' => 'DIRTY',
            'total_ng' => 5,
            'ng_input_item' => '[5] NG-PROCESS',
            'ng_input_qty' => 5,
        ]);

        $this->artisan('second-process:standardize-ng-records')
            ->expectsConfirmation('Are you sure you want to update 1 records in the database?', 'no')
            ->expectsOutputToContain('Operation aborted by user')
            ->assertExitCode(1);

        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $record->id,
            'ng_category' => 'NG-PROCESS',
        ]);
    }

    public function test_standardizes_all_variants_and_backfills_null_categories(): void
    {
        $report = $this->createReport();

        // 1. NG-PROCESS variant in category and input
        $rec1 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => 'NG-PROCESS',
            'ng_name' => 'FELL OFF',
            'total_ng' => 4,
            'ng_input_item' => '[4] NG-PROCESS',
            'ng_input_qty' => 4,
        ]);

        // 2. NG-PROCES (single 's' typo)
        $rec2 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => 'NG-PROCES',
            'ng_name' => 'DIRTY',
            'total_ng' => 6,
            'ng_input_item' => '[6] NG-PROCES',
            'ng_input_qty' => 6,
        ]);

        // 3. Multi-item input remark with NG-PROCESS
        $rec3 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => 'NG-PROSES',
            'ng_name' => 'BINTIK',
            'total_ng' => 5,
            'ng_input_item' => '[2] NG-INPUT | [3] NG-PROCESS',
            'ng_input_qty' => 5,
        ]);

        // 4. Null category with NG-PROSES in input
        $rec4 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => null,
            'ng_name' => 'HAIR MARK',
            'total_ng' => 11,
            'ng_input_item' => '[11] NG-PROSES',
            'ng_input_qty' => 11,
        ]);

        // 5. Null category with NG-INPUT in input
        $rec5 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => null,
            'ng_name' => 'DENTED',
            'total_ng' => 7,
            'ng_input_item' => '[7] NG-INPUT',
            'ng_input_qty' => 7,
        ]);

        // 6. Null category with custom remark text (e.g. Oil) - note preserved in remark, standardized to NG-PROSES
        $rec6 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => null,
            'ng_name' => 'DIRTY',
            'total_ng' => 10,
            'ng_input_item' => 'Oil',
            'ng_input_qty' => null,
            'remark' => null,
        ]);

        // 7. Null category with WIP marker - note preserved in remark, standardized to NG-INPUT
        $rec7 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => null,
            'ng_name' => 'DENTED',
            'total_ng' => 4,
            'ng_input_item' => '[4] PART-WIP',
            'ng_input_qty' => 4,
            'remark' => null,
        ]);

        // 8. Zero total NG with text - note preserved in remark, ng_input_item cleared to null
        $rec8 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => null,
            'ng_name' => 'DENTED',
            'total_ng' => 0,
            'ng_input_item' => 'Sagging',
            'ng_input_qty' => null,
            'remark' => null,
        ]);

        // 9. Already standard record - should NOT be changed
        $rec9 = SecondProcessNgRecord::create([
            'report_id' => $report->id,
            'ng_category' => 'NG-PROSES',
            'ng_name' => 'SCRATCH',
            'total_ng' => 2,
            'ng_input_item' => '[2] NG-PROSES',
            'ng_input_qty' => 2,
        ]);

        $this->artisan('second-process:standardize-ng-records', ['--force' => true])
            ->expectsOutputToContain('Successfully updated 8 records in second_process_ng_records')
            ->assertExitCode(0);

        // Verify rec1: NG-PROCESS -> NG-PROSES
        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec1->id,
            'ng_category' => 'NG-PROSES',
            'ng_input_item' => '[4] NG-PROSES',
        ]);

        // Verify rec2: NG-PROCES -> NG-PROSES
        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec2->id,
            'ng_category' => 'NG-PROSES',
            'ng_input_item' => '[6] NG-PROSES',
        ]);

        // Verify rec3: input string normalized in multi-item
        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec3->id,
            'ng_category' => 'NG-PROSES',
            'ng_input_item' => '[2] NG-INPUT | [3] NG-PROSES',
        ]);

        // Verify rec4: null category backfilled to NG-PROSES
        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec4->id,
            'ng_category' => 'NG-PROSES',
            'ng_input_item' => '[11] NG-PROSES',
        ]);

        // Verify rec5: null category backfilled to NG-INPUT
        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec5->id,
            'ng_category' => 'NG-INPUT',
            'ng_input_item' => '[7] NG-INPUT',
        ]);

        // Verify rec6: custom text note copied to remark, standardized to [10] NG-PROSES
        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec6->id,
            'ng_category' => 'NG-PROSES',
            'ng_input_item' => '[10] NG-PROSES',
            'ng_input_qty' => 10,
            'remark' => 'Oil',
        ]);

        // Verify rec7: WIP marker copied to remark, standardized to [4] NG-INPUT
        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec7->id,
            'ng_category' => 'NG-INPUT',
            'ng_input_item' => '[4] NG-INPUT',
            'ng_input_qty' => 4,
            'remark' => '[4] PART-WIP',
        ]);

        // Verify rec8: zero total_ng has note copied to remark, ng_input_item cleared
        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec8->id,
            'ng_input_item' => null,
            'ng_input_qty' => null,
            'remark' => 'Sagging',
        ]);

        // Verify rec9: remained untouched
        $this->assertDatabaseHas('second_process_ng_records', [
            'id' => $rec9->id,
            'ng_category' => 'NG-PROSES',
            'ng_input_item' => '[2] NG-PROSES',
        ]);

        // 10. Idempotency test: Running again should find 0 candidates / 0 updates needed
        $this->artisan('second-process:standardize-ng-records', ['--force' => true])
            ->expectsOutputToContain('All second_process_ng_records are already standardized')
            ->assertExitCode(0);
    }
}
