<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\StoreBoxData;
use App\Models\StoreBoxDetail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PackagingZebraLabelTest extends TestCase
{
    protected User $user;

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

        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $this->user = User::create([
            'name' => 'Store Admin',
            'email' => 'store@test.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);
    }

    private function createTestSchema(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('store_box_data', function (Blueprint $table) {
            $table->id();
            $table->string('part_no')->unique();
            $table->string('part_name')->nullable();
            $table->timestamps();
        });

        Schema::create('store_box_details', function (Blueprint $table) {
            $table->id();
            $table->string('part_no');
            $table->integer('label');
            $table->string('status')->default('active');
            $table->string('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function test_packaging_zebra_30x20_label_displays_large_label_number_and_wib_time(): void
    {
        $response = $this->actingAs($this->user)->post(route('generate.zebra.barcode30x20'), [
            'partNo' => '601-DAIJO19/BOX PP BOARD t5 410X340X180',
            'print_quantity' => 2,
        ]);

        $response->assertStatus(200);

        // Assert large label number is rendered
        $response->assertSee('label-number');
        $response->assertSee('#1');
        $response->assertSee('#2');

        // Assert part_no and part_name are NOT rendered in the right info column
        $response->assertDontSee('<div class="part-no"', false);
        $response->assertDontSee('<div class="part-name"', false);

        // Assert time is formatted with WIB
        $response->assertSee('WIB');

        // Assert item code is rendered below QR code in qr-col
        $response->assertSee('qr-item-code');
        $response->assertSee('601-DAIJO19');

        // Assert autoFitLabels script is present
        $response->assertSee('autoFitLabels');
    }

    public function test_packaging_zebra_30x20_label_auto_sizes_large_label_numbers(): void
    {
        // Create an existing record so the next label number will be 1027
        StoreBoxDetail::create([
            'part_no' => '601-68921B000P',
            'label' => 1026,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->post(route('generate.zebra.barcode30x20'), [
            'partNo' => '601-68921B000P/SAMPLE PART',
            'print_quantity' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertSee('#1027');
        $response->assertSee('#1028');
        // Label with 5 characters (#1027) gets optimal proportional pt size (9.8pt)
        $response->assertSee('font-size: 9.8pt');
    }

    public function test_store_box_detail_reprint_button_defaults_to_30x20_format(): void
    {
        $detail = StoreBoxDetail::create([
            'part_no' => '601-68921B000P',
            'label' => 1027,
            'status' => 'active',
        ]);

        $indexResponse = $this->actingAs($this->user)->get(route('barcode.box_detail.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('<input type="hidden" name="format" value="zebra30x20">', false);

        // Test sending reprint request with format zebra30x20
        $reprintResponse = $this->actingAs($this->user)->post(route('barcode.reprint'), [
            'ids' => [$detail->id],
            'format' => 'zebra30x20',
        ]);

        $reprintResponse->assertStatus(200);
        $reprintResponse->assertSee('Zebra / Argox Label 30x20');
        $reprintResponse->assertSee('#1027');
        $reprintResponse->assertSee('601-68921B000P');
    }
}
