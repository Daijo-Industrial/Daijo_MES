<?php

namespace Tests\Feature;

use App\Mail\SpkPlannedQuantityChangedMail;
use App\Models\MasterListItem;
use App\Models\SpkMaster;
use App\Services\SpkMasterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SpkPlannedQuantityAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_sends_email_to_intan_and_sisca_when_planned_qty_changes(): void
    {
        Mail::fake();

        \Illuminate\Support\Facades\DB::table('master_list_items')->insert([
            'item_code' => '1360A153.',
            'item_name' => 'SHROUD ASSY RADIATOR',
            'tipe_mesin' => 'INJECTION',
            'standart_packaging_list' => 10,
        ]);

        $service = app(SpkMasterService::class);
        $batchId = 'SYNC-20261106-001';

        $changes = [
            [
                'spk_number' => '26030890',
                'item_code' => '1360A153.',
                'old_planned_qty' => 500,
                'new_planned_qty' => 450,
                'old_status' => 'R',
                'new_status' => 'R',
            ],
            [
                'spk_number' => '26031204',
                'item_code' => 'CFTA-A705QBKZ',
                'old_planned_qty' => 3000,
                'new_planned_qty' => 3300,
                'old_status' => 'R',
                'new_status' => 'R',
            ],
        ];

        $service->notifyPlannedQuantityChanges($changes, $batchId);

        Mail::assertSent(SpkPlannedQuantityChangedMail::class, function ($mail) use ($batchId) {
            $hasIntan = $mail->hasTo('intan@daijo.co.id');
            $hasSisca = $mail->hasTo('sisca@daijo.co.id');
            $batchMatch = $mail->batchId === $batchId;
            $countMatch = count($mail->changes) === 2;

            $firstItem = $mail->changes[0];
            $diffMatch = $firstItem['planned_diff'] === -50;
            $nameMatch = $firstItem['item_name'] === 'SHROUD ASSY RADIATOR';

            return $hasIntan && $hasSisca && $batchMatch && $countMatch && $diffMatch && $nameMatch;
        });
    }

    public function test_empty_planned_changes_does_not_send_email(): void
    {
        Mail::fake();

        $service = app(SpkMasterService::class);
        $service->notifyPlannedQuantityChanges([], 'BATCH-EMPTY');

        Mail::assertNothingSent();
    }

    public function test_artisan_test_command_sends_email_properly(): void
    {
        Mail::fake();

        $this->artisan('spk:test-planned-alert')
            ->assertSuccessful();

        Mail::assertSent(SpkPlannedQuantityChangedMail::class, function ($mail) {
            return $mail->hasTo('intan@daijo.co.id')
                && $mail->hasTo('sisca@daijo.co.id')
                && $mail->isTest === true;
        });
    }

    public function test_sync_data_triggers_alert_only_when_planned_qty_changes(): void
    {
        Mail::fake();

        // 1. Setup SPK awal di DB
        SpkMaster::create([
            'spk_number' => '26030890',
            'item_code' => '1360A153.',
            'planned_quantity' => 500,
            'completed_quantity' => 100,
            'production_status' => 'R',
            'post_date' => '2026-11-06',
            'due_date' => '2026-11-07',
            'warehouse' => 'MWH-01',
        ]);

        SpkMaster::create([
            'spk_number' => '26039999',
            'item_code' => 'TEST-COMPLETED-ONLY',
            'planned_quantity' => 1000,
            'completed_quantity' => 200,
            'production_status' => 'R',
            'post_date' => '2026-11-06',
            'due_date' => '2026-11-07',
            'warehouse' => 'MWH-01',
        ]);

        // Mock SAP Login & Production Order List
        Http::fake([
            '*/auth/token' => Http::response(['access_token' => 'dummy-token'], 200),
            '*/api/sap_production_order/list' => Http::response([
                'success' => true,
                'data' => [
                    [
                        'SPKNo' => '26030890',
                        'ItemCode' => '1360A153.',
                        'PlannedQty' => '450', // Planned berubah: 500 -> 450
                        'CompletedQty' => '100',
                        'Status' => 'R',
                        'PostDate' => '06/11/2026',
                        'DueDate' => '07/11/2026',
                        'Warehouse' => 'MWH-01',
                    ],
                    [
                        'SPKNo' => '26039999',
                        'ItemCode' => 'TEST-COMPLETED-ONLY',
                        'PlannedQty' => '1000', // Planned TETAP 1000
                        'CompletedQty' => '250', // Hanya completed bertambah: 200 -> 250
                        'Status' => 'R',
                        'PostDate' => '06/11/2026',
                        'DueDate' => '07/11/2026',
                        'Warehouse' => 'MWH-01',
                    ],
                ],
            ], 200),
        ]);

        $service = app(SpkMasterService::class);
        $response = $service->SyncData();

        $this->assertEquals(200, $response->getStatusCode());

        // Pastikan hanya 1 email yang dikirim berisi SPK 26030890 (bukan SPK 26039999)
        Mail::assertSent(SpkPlannedQuantityChangedMail::class, function ($mail) {
            $changes = $mail->changes;
            $spkNumbers = collect($changes)->pluck('spk_number')->toArray();

            return in_array('26030890', $spkNumbers)
                && ! in_array('26039999', $spkNumbers)
                && count($changes) === 1;
        });
    }
}

