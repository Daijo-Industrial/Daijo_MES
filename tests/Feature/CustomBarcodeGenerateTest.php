<?php

namespace Tests\Feature;

use App\Models\CustomBarcodeLog;
use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomBarcodeGenerateTest extends TestCase
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
            $table->timestamps();
        });

        Schema::create('master_list_items', function ($table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name')->nullable();
            $table->string('tipe_mesin')->nullable();
            $table->integer('standart_packaging_list')->nullable();
            $table->integer('setup_time_minute')->nullable();
            $table->integer('pair')->nullable();
            $table->integer('cavity')->nullable();
            $table->string('customer_code')->nullable();
            $table->decimal('cycle_time', 8, 2)->nullable();
            $table->string('project_code')->nullable();
            $table->string('family')->nullable();
            $table->decimal('part_weight', 10, 3)->nullable();
            $table->string('description_in_foreign_lang')->nullable();
            $table->string('color')->nullable();
            $table->string('half_code_1')->nullable();
            $table->string('half_code_2')->nullable();
            $table->string('position')->nullable();
        });

        Schema::create('custom_barcode_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('item_code')->index();
            $table->string('item_name')->nullable();
            $table->string('spk_number')->index();
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
    }

    public function test_custom_generate_form_renders_with_items_and_snp(): void
    {
        $role = Role::create(['name' => 'ADMIN']);
        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);

        MasterListItem::create([
            'item_code' => 'PART-WITH-SNP',
            'item_name' => 'Part With Standard Pack',
            'standart_packaging_list' => 75,
        ]);

        MasterListItem::create([
            'item_code' => 'PART-EMPTY-SNP',
            'item_name' => 'Part Without Standard Pack',
            'standart_packaging_list' => null,
        ]);

        $response = $this->actingAs($user)->get('/barcode/custom-generate');

        $response->assertStatus(200);
        $response->assertSee('PART-WITH-SNP');
        $response->assertSee('PART-EMPTY-SNP');
        $response->assertSee('"std_pack":75', false);
        $response->assertSee('"std_pack":0', false);
        $response->assertSee('min="0"', false);
    }

    public function test_custom_generate_print_allows_quantity_zero(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Operator Test',
            'email' => 'operator@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);

        MasterListItem::create([
            'item_code' => 'PART-001',
            'item_name' => 'Test Item 001',
            'standart_packaging_list' => 0,
        ]);

        $payload = [
            'item_code' => 'PART-001',
            'spk_number' => 'SPK-999',
            'quantity' => 0,
            'warehouse' => 'WFI',
            'start_label' => 1,
            'end_label' => 1,
            'shift' => 'I',
            'prod_date' => '2026-10-30',
            'operator' => 'Budi',
            'customer' => 'PT Daijo',
            'barcode_type' => 'default',
        ];

        $response = $this->actingAs($user)->post('/barcode/custom-generate/print', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('custom_barcode_logs', [
            'item_code' => 'PART-001',
            'quantity' => 0,
            'spk_number' => 'SPK-999',
        ]);
    }

    public function test_custom_generate_print_rejects_negative_quantity(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Operator Test',
            'email' => 'operator2@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);

        $payload = [
            'item_code' => 'PART-001',
            'spk_number' => 'SPK-999',
            'quantity' => -5,
            'warehouse' => 'WFI',
            'start_label' => 1,
            'end_label' => 1,
            'shift' => 'I',
        ];

        $response = $this->actingAs($user)->post('/barcode/custom-generate/print', $payload);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_custom_generate_print_accepts_positive_snp_quantity(): void
    {
        $role = Role::create(['name' => 'OPERATOR']);
        $user = User::create([
            'name' => 'Operator Test',
            'email' => 'operator3@example.com',
            'password' => bcrypt('secret'),
            'role_id' => $role->id,
        ]);

        MasterListItem::create([
            'item_code' => 'PART-002',
            'item_name' => 'Test Item 002',
            'standart_packaging_list' => 120,
        ]);

        $payload = [
            'item_code' => 'PART-002',
            'spk_number' => 'SPK-1001',
            'quantity' => 120,
            'warehouse' => 'WFI',
            'start_label' => 1,
            'end_label' => 2,
            'shift' => 'II',
            'prod_date' => '2026-10-30',
            'operator' => 'Joko',
            'customer' => 'PT Sharp',
            'barcode_type' => 'sharp',
        ];

        $response = $this->actingAs($user)->post('/barcode/custom-generate/print', $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('custom_barcode_logs', [
            'item_code' => 'PART-002',
            'quantity' => 120,
            'spk_number' => 'SPK-1001',
            'total_labels' => 2,
        ]);
    }
}
