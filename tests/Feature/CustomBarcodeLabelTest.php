<?php

namespace Tests\Feature;

use App\Models\CustomBarcodeLog;
use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomBarcodeLabelTest extends TestCase
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

        Schema::create('master_list_items', function ($table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name')->nullable();
            $table->string('description_in_foreign_lang')->nullable();
            $table->string('family')->nullable();
            $table->decimal('part_weight', 10, 3)->nullable()->default(0);
            $table->integer('standart_packaging_list')->nullable()->default(0);
            $table->string('color')->nullable();
            $table->string('position')->nullable();
            $table->string('half_code_1')->nullable();
            $table->string('half_code_2')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('custom_barcode_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('item_code');
            $table->string('item_name')->nullable();
            $table->string('spk_number');
            $table->integer('quantity');
            $table->string('warehouse')->default('WFI');
            $table->string('shift', 10);
            $table->integer('start_label');
            $table->integer('end_label');
            $table->integer('total_labels');
            $table->date('prod_date')->nullable();
            $table->string('operator')->nullable();
            $table->string('customer')->nullable();
            $table->string('barcode_type')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_log', function ($table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->string('subject_id', 191)->nullable();
            $table->string('causer_type')->nullable();
            $table->string('causer_id', 191)->nullable();
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->string('event')->nullable();
            $table->timestamps();
        });
    }

    public function test_custom_barcode_print_with_empty_date()
    {
        $role = Role::create(['name' => 'ADMIN']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role_id' => $role->id,
            'password' => bcrypt('password'),
        ]);

        MasterListItem::create([
            'item_code' => 'ITEM-001',
            'item_name' => 'SAMPLE ITEM',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('barcode.custom.print'), [
            'item_code' => 'ITEM-001',
            'spk_number' => 'SPK-9999',
            'quantity' => 100,
            'warehouse' => 'WFI',
            'start_label' => 1,
            'end_label' => 2,
            'shift' => 'I',
            'prod_date' => '',
            'barcode_type' => 'default',
        ]);

        $response->assertOk();
        $response->assertViewHas('labels', function ($labels) {
            return count($labels) === 2
                && $labels[0]['prod_date'] === null
                && $labels[0]['prod_date_formatted'] === '';
        });

        // Ensure logged correctly with null prod_date
        $log = CustomBarcodeLog::first();
        $this->assertNotNull($log);
        $this->assertNull($log->prod_date);
    }

    public function test_custom_barcode_print_with_filled_date()
    {
        $user = User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
        ]);

        MasterListItem::create([
            'item_code' => 'ITEM-SHARP',
            'item_name' => 'SHARP ITEM',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('barcode.custom.print'), [
            'item_code' => 'ITEM-SHARP',
            'spk_number' => 'SPK-SHARP-01',
            'quantity' => 50,
            'warehouse' => 'FFI',
            'start_label' => 1,
            'end_label' => 1,
            'shift' => 'II',
            'prod_date' => '2026-09-07',
            'barcode_type' => 'sharp',
        ]);

        $response->assertOk();
        $response->assertViewHas('labels', function ($labels) {
            return $labels[0]['prod_date'] === '2026-09-07'
                && $labels[0]['prod_date_formatted'] === 'SEPTEMBER 2026'
                && $labels[0]['year_code'] === 'BF'
                && (string) $labels[0]['month_code'] === '9';
        });
    }

    public function test_itsp_half_code_enlarges_half_code_1_when_position_is_left()
    {
        $user = User::create([
            'name' => 'ITSP User Left',
            'email' => 'itsp_left@example.com',
            'password' => bcrypt('password'),
        ]);

        MasterListItem::create([
            'item_code' => 'TSPT10188XA-INJ',
            'item_name' => 'PANEL CONSOLE RR SIDE LH',
            'half_code_1' => 'TSPT10',
            'half_code_2' => '188XA',
            'position' => 'Left',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('barcode.custom.print'), [
            'item_code' => 'TSPT10188XA-INJ',
            'spk_number' => 'SPK-ITSP-01',
            'quantity' => 80,
            'warehouse' => 'WFI',
            'start_label' => 1,
            'end_label' => 1,
            'shift' => 'I',
            'barcode_type' => 'itsp',
            'position' => 'Left',
        ]);

        $response->assertOk();
        $response->assertViewHas('labels', function ($labels) {
            return $labels[0]['position'] === 'LH'
                && $labels[0]['enlarged_half_code'] === 1;
        });

        // Verify that half_code_1 has font-size 16pt and half_code_2 has font-size 11pt
        $content = $response->getContent();
        $this->assertStringContainsString('font-size: 16pt; font-weight: 900;">TSPT10</span><span style="font-size: 11pt; font-weight: 900;">188XA</span>', $content);
    }

    public function test_itsp_half_code_enlarges_half_code_2_when_position_is_right()
    {
        $user = User::create([
            'name' => 'ITSP User Right',
            'email' => 'itsp_right@example.com',
            'password' => bcrypt('password'),
        ]);

        MasterListItem::create([
            'item_code' => 'TSPT10188XA-INJ',
            'item_name' => 'PANEL CONSOLE RR SIDE RH',
            'half_code_1' => 'TSPT10',
            'half_code_2' => '188XA',
            'position' => 'Right',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('barcode.custom.print'), [
            'item_code' => 'TSPT10188XA-INJ',
            'spk_number' => 'SPK-ITSP-02',
            'quantity' => 80,
            'warehouse' => 'WFI',
            'start_label' => 1,
            'end_label' => 1,
            'shift' => 'I',
            'barcode_type' => 'itsp',
            'position' => 'Right',
        ]);

        $response->assertOk();
        $response->assertViewHas('labels', function ($labels) {
            return $labels[0]['position'] === 'RH'
                && $labels[0]['enlarged_half_code'] === 2;
        });

        // Verify that half_code_1 has font-size 11pt and half_code_2 has font-size 16pt
        $content = $response->getContent();
        $this->assertStringContainsString('font-size: 11pt; font-weight: 900;">TSPT10</span><span style="font-size: 16pt; font-weight: 900;">188XA</span>', $content);
    }

    public function test_itsp_half_code_uniform_size_when_position_is_center()
    {
        $user = User::create([
            'name' => 'ITSP User Center',
            'email' => 'itsp_center@example.com',
            'password' => bcrypt('password'),
        ]);

        MasterListItem::create([
            'item_code' => 'TSPT10188XA-INJ',
            'item_name' => 'PANEL CONSOLE RR SIDE CTR',
            'half_code_1' => 'TSPT10',
            'half_code_2' => '188XA',
            'position' => 'Center',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('barcode.custom.print'), [
            'item_code' => 'TSPT10188XA-INJ',
            'spk_number' => 'SPK-ITSP-03',
            'quantity' => 80,
            'warehouse' => 'WFI',
            'start_label' => 1,
            'end_label' => 1,
            'shift' => 'I',
            'barcode_type' => 'itsp',
            'position' => 'Center',
        ]);

        $response->assertOk();
        $response->assertViewHas('labels', function ($labels) {
            return $labels[0]['position'] === 'CTR'
                && $labels[0]['enlarged_half_code'] === 0;
        });

        // Verify that both half codes have uniform font-size 13.5pt and neither is 16pt or 11pt
        $content = $response->getContent();
        $this->assertStringContainsString('font-size: 13.5pt; font-weight: 900;">TSPT10</span><span style="font-size: 13.5pt; font-weight: 900;">188XA</span>', $content);
        $this->assertStringNotContainsString('font-size: 16pt;', $content);
    }

    public function test_itsp_half_code_and_rh_lh_independent_left_position_with_rh_side()
    {
        $user = User::create([
            'name' => 'ITSP Independent User',
            'email' => 'itsp_indep@example.com',
            'password' => bcrypt('password'),
        ]);

        MasterListItem::create([
            'item_code' => 'TSPT10188XA-INJ',
            'item_name' => 'PANEL CONSOLE RR SIDE RH',
            'half_code_1' => 'TSPT10',
            'half_code_2' => '188XA',
            'position' => 'Left',
        ]);

        $this->actingAs($user);

        // Position is Left (enlarges half_code_1), but part side rh_lh is explicitly RH
        $response = $this->post(route('barcode.custom.print'), [
            'item_code' => 'TSPT10188XA-INJ',
            'spk_number' => 'SPK-ITSP-03',
            'quantity' => 80,
            'warehouse' => 'WFI',
            'start_label' => 1,
            'end_label' => 1,
            'shift' => 'I',
            'barcode_type' => 'itsp',
            'position' => 'Left',
            'rh_lh' => 'RH',
        ]);

        $response->assertOk();
        $response->assertViewHas('labels', function ($labels) {
            return $labels[0]['rh_lh'] === 'RH'
                && $labels[0]['position'] === 'RH'
                && $labels[0]['enlarged_half_code'] === 1;
        });

        $content = $response->getContent();
        // Half code 1 must still be enlarged (16pt) because position was Left
        $this->assertStringContainsString('font-size: 16pt; font-weight: 900;">TSPT10</span><span style="font-size: 11pt; font-weight: 900;">188XA</span>', $content);
        // The bottom position cell must display RH
        $this->assertStringContainsString('<div class="itsp-pos-cell">RH</div>', $content);
    }

    public function test_itsp_half_code_and_rh_lh_independent_right_position_with_lh_side()
    {
        $user = User::create([
            'name' => 'ITSP Independent User 2',
            'email' => 'itsp_indep2@example.com',
            'password' => bcrypt('password'),
        ]);

        MasterListItem::create([
            'item_code' => 'TSPT10188XA-INJ',
            'item_name' => 'PANEL CONSOLE RR SIDE LH',
            'half_code_1' => 'TSPT10',
            'half_code_2' => '188XA',
            'position' => 'Right',
        ]);

        $this->actingAs($user);

        // Position is Right (enlarges half_code_2), but part side rh_lh is explicitly LH
        $response = $this->post(route('barcode.custom.print'), [
            'item_code' => 'TSPT10188XA-INJ',
            'spk_number' => 'SPK-ITSP-04',
            'quantity' => 80,
            'warehouse' => 'WFI',
            'start_label' => 1,
            'end_label' => 1,
            'shift' => 'I',
            'barcode_type' => 'itsp',
            'position' => 'Right',
            'rh_lh' => 'LH',
        ]);

        $response->assertOk();
        $response->assertViewHas('labels', function ($labels) {
            return $labels[0]['rh_lh'] === 'LH'
                && $labels[0]['position'] === 'LH'
                && $labels[0]['enlarged_half_code'] === 2;
        });

        $content = $response->getContent();
        // Half code 2 must still be enlarged (16pt) because position was Right
        $this->assertStringContainsString('font-size: 11pt; font-weight: 900;">TSPT10</span><span style="font-size: 16pt; font-weight: 900;">188XA</span>', $content);
        // The bottom position cell must display LH
        $this->assertStringContainsString('<div class="itsp-pos-cell">LH</div>', $content);
    }

    public function test_vinfast_label_generation_with_item_details()
    {
        $user = User::create([
            'name' => 'VinFast User',
            'email' => 'vinfast@example.com',
            'password' => bcrypt('password'),
        ]);

        MasterListItem::create([
            'item_code' => 'BIN37024015AA',
            'item_name' => 'UPPER RH C PILLAR ASSEMBLY',
            'family' => 'VF5',
            'part_weight' => 5.5,
            'standart_packaging_list' => 6,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('barcode.custom.print'), [
            'item_code' => 'BIN37024015AA',
            'spk_number' => 'SPK-VF-9988',
            'quantity' => 6,
            'warehouse' => 'FFI',
            'start_label' => 1,
            'end_label' => 2,
            'shift' => 'I',
            'prod_date' => '2026-10-07',
            'barcode_type' => 'vinfast',
        ]);

        $response->assertOk();
        $response->assertViewHas('labels', function ($labels) {
            return count($labels) === 2
                && $labels[0]['item_code'] === 'BIN37024015AA'
                && $labels[0]['item_name'] === 'UPPER RH C PILLAR ASSEMBLY'
                && $labels[0]['model'] === 'VF5'
                && (float) $labels[0]['part_weight'] === 5.5
                && (int) $labels[0]['std_pack'] === 6
                && $labels[0]['made_in'] === 'Indonesia'
                && $labels[0]['prod_date_formatted'] === '7 OCT 2026';
        });

        $content = $response->getContent();
        $this->assertStringContainsString('VINFAST TRADING AND PRODUCTION JSC', $content);
        $this->assertStringContainsString('PT. CHANGMING AUTO INDONESIA', $content);
        $this->assertStringContainsString('BIN37024015AA', $content);
        $this->assertStringContainsString('UPPER RH C PILLAR ASSEMBLY', $content);
        $this->assertStringContainsString('VF5', $content);
        $this->assertStringContainsString('5.5', $content);
        $this->assertStringContainsString('7 OCT 2026', $content);
        $this->assertStringContainsString('INDIVIDUAL', $content);
        $this->assertStringContainsString('SPK-VF-9988', $content);
    }

    public function test_vinfast_label_generation_without_spk_number_succeeds()
    {
        $user = User::create([
            'name' => 'VinFast User No SPK',
            'email' => 'vinfast_no_spk@example.com',
            'password' => bcrypt('password'),
        ]);

        MasterListItem::create([
            'item_code' => 'BIN37024015AA',
            'item_name' => 'UPPER RH C PILLAR ASSEMBLY',
            'family' => 'VF5',
            'part_weight' => 5.5,
            'standart_packaging_list' => 6,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('barcode.custom.print'), [
            'item_code' => 'BIN37024015AA',
            'spk_number' => '', // empty SPK for VinFast
            'quantity' => 6,
            'warehouse' => 'FFI',
            'start_label' => 1,
            'end_label' => 1,
            'shift' => 'I',
            'prod_date' => '2026-10-07',
            'barcode_type' => 'vinfast',
        ]);

        $response->assertOk();
        $response->assertViewHas('labels', function ($labels) {
            return count($labels) === 1
                && $labels[0]['item_code'] === 'BIN37024015AA'
                && $labels[0]['spk_number'] === '-';
        });

        $log = CustomBarcodeLog::where('item_code', 'BIN37024015AA')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals('-', $log->spk_number);
    }

    public function test_default_barcode_generation_without_spk_number_fails_validation()
    {
        $user = User::create([
            'name' => 'Standard User',
            'email' => 'standard_user@example.com',
            'password' => bcrypt('password'),
        ]);

        MasterListItem::create([
            'item_code' => 'ITEM-DEFAULT',
            'item_name' => 'DEFAULT ITEM',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('barcode.custom.print'), [
            'item_code' => 'ITEM-DEFAULT',
            'spk_number' => '',
            'quantity' => 10,
            'warehouse' => 'WFI',
            'start_label' => 1,
            'end_label' => 1,
            'shift' => 'I',
            'barcode_type' => 'default',
        ]);

        $response->assertSessionHasErrors('spk_number');
    }
}
