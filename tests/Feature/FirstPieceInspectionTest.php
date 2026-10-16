<?php

namespace Tests\Feature;

use App\Models\FirstPieceInspection;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstPieceInspectionTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminRole = Role::create(['name' => 'ADMIN']);
        $this->user = User::factory()->create(['role_id' => $this->adminRole->id]);
    }

    public function test_first_piece_index_requires_authentication(): void
    {
        $response = $this->get(route('first-piece-inspections.index'));
        $response->assertRedirect('/login');
    }

    public function test_first_piece_index_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get(route('first-piece-inspections.index'));
        $response->assertOk();
    }

    public function test_can_create_first_piece_inspection_with_ok_judgement(): void
    {
        $payload = [
            'date' => '2026-07-27',
            'model' => 'KS PE',
            'part_name' => 'Molding Side REF',
            'part_number' => '401-41019967',
            'paint_code' => 'DR 249-8M8',
            'thinner_code' => 'T971',
            'ink_code' => 'INK-01',
            'viscosity' => '10s',
            'cycle_time' => '30s',
            'remark' => 'First piece approved',
            'check_results' => [
                ['check_point' => 'Dirty Spray', 'method' => 'Visual', 'result' => 'OK', 'judgement' => 'OK'],
                ['check_point' => 'Over Spray', 'method' => 'Visual', 'result' => 'OK', 'judgement' => 'OK'],
                ['check_point' => 'Custom Surface Scratch', 'method' => 'Gauge', 'result' => 'OK', 'judgement' => 'OK'],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('first-piece-inspections.store'), $payload);

        $inspection = FirstPieceInspection::latest('id')->first();
        $this->assertNotNull($inspection);
        $this->assertEquals('OK', $inspection->overall_judgement);
        $this->assertEquals('401-41019967', $inspection->part_number);

        $response->assertRedirect(route('first-piece-inspections.show', $inspection->id));
    }

    public function test_can_create_first_piece_inspection_without_model(): void
    {
        $payload = [
            'date' => '2026-07-27',
            'model' => null, // Nullable model test
            'part_name' => 'Molding Side REF',
            'part_number' => '401-41019967',
            'check_results' => [
                ['check_point' => 'Dirty Spray', 'method' => 'Visual', 'result' => 'OK', 'judgement' => 'OK'],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('first-piece-inspections.store'), $payload);

        $inspection = FirstPieceInspection::latest('id')->first();
        $this->assertNotNull($inspection);
        $this->assertNull($inspection->model);
        $this->assertEquals('OK', $inspection->overall_judgement);
    }

    public function test_first_piece_inspection_evaluates_ng_when_any_check_fails(): void
    {
        $payload = [
            'date' => '2026-07-27',
            'model' => 'KS PE',
            'part_name' => 'Molding Side REF',
            'part_number' => '401-41019967',
            'check_results' => [
                ['check_point' => 'Dirty Spray', 'method' => 'Visual', 'result' => 'OK', 'judgement' => 'OK'],
                ['check_point' => 'Over Spray', 'method' => 'Visual', 'result' => 'NG', 'judgement' => 'NG'],
            ],
        ];

        $this->actingAs($this->user)->post(route('first-piece-inspections.store'), $payload);

        $inspection = FirstPieceInspection::latest('id')->first();
        $this->assertEquals('NG', $inspection->overall_judgement);
    }

    public function test_signature_workflow(): void
    {
        $inspection = FirstPieceInspection::create([
            'date' => '2026-07-27',
            'model' => 'KS PE',
            'part_name' => 'Molding Side REF',
            'part_number' => '401-41019967',
            'overall_judgement' => 'OK',
        ]);

        // 1. Production signs
        $this->actingAs($this->user)->post(route('first-piece-inspections.sign', [$inspection->id, 'prepared']));
        $inspection->refresh();
        $this->assertEquals($this->user->name, $inspection->prepared_by);

        // 2. QC Inspector signs
        $this->actingAs($this->user)->post(route('first-piece-inspections.sign', [$inspection->id, 'checked']));
        $inspection->refresh();
        $this->assertEquals($this->user->name, $inspection->checked_by);
        $this->assertTrue($inspection->isApproved());

        // 3. QC Leader signs
        $this->actingAs($this->user)->post(route('first-piece-inspections.sign', [$inspection->id, 'approved']));
        $inspection->refresh();
        $this->assertEquals($this->user->name, $inspection->approved_by);
    }

    public function test_check_approval_api_endpoint(): void
    {
        // 1. Query for non-existent inspection
        $res = $this->actingAs($this->user)->getJson(route('first-piece-inspections.check-approval', [
            'part_number' => '401-41019967',
            'date' => '2026-07-27',
        ]));
        $res->assertOk();
        $res->assertJson(['approved' => false]);

        // 2. Create and approve inspection
        $inspection = FirstPieceInspection::create([
            'date' => '2026-07-27',
            'model' => 'KS PE',
            'part_name' => 'Molding Side REF',
            'part_number' => '401-41019967',
            'overall_judgement' => 'OK',
            'checked_by' => 'QC Inspector 1',
            'checked_at' => now(),
        ]);

        // 3. Query again
        $res2 = $this->actingAs($this->user)->getJson(route('first-piece-inspections.check-approval', [
            'part_number' => '401-41019967',
            'date' => '2026-07-27',
        ]));
        $res2->assertOk();
        $res2->assertJson(['approved' => true]);
    }
}
