<?php

namespace Tests\Feature;

use App\Livewire\Admin\MasterListManager;
use App\Models\DailyItemCode;
use App\Models\MasterBusinessPartner;
use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MasterListDailyItemCodeVerificationTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Gunakan sqlite in-memory
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->createTestSchema();

        $role = Role::firstOrCreate(['name' => 'ADMIN']);
        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
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
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name')->nullable();
            $table->string('customer_code')->nullable();
            $table->string('tipe_mesin')->nullable();
            $table->integer('standart_packaging_list')->default(0);
            $table->integer('setup_time_minute')->default(0);
            $table->string('pair')->default('0');
            $table->integer('cavity')->default(1);
            $table->double('cycle_time')->default(0);
            $table->string('project_code')->nullable();
            $table->string('family')->default('0');
            $table->double('part_weight')->default(0);
            $table->string('description_in_foreign_lang')->default('0');
            $table->string('color')->default('0');
            $table->string('half_code_1')->default('0');
            $table->string('half_code_2')->default('0');
            $table->string('position')->default('0');
        });

        Schema::create('master_business_partners', function (Blueprint $table) {
            $table->id();
            $table->string('bp_code')->unique();
            $table->string('bp_name');
            $table->string('bp_type')->default('Customer');
            $table->string('category')->default('Customer');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('master_customer_delivery', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('customer_name');
            $table->timestamps();
        });

        Schema::create('daily_item_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('item_code');
            $table->integer('quantity')->default(0);
            $table->integer('shift')->default(1);
            $table->date('schedule_date');
            $table->integer('is_done')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('master_item_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('item_code');
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->unsignedBigInteger('upload_batch_id')->nullable();
            $table->string('file_name')->nullable();
            $table->timestamps();
        });
    }

    public function test_master_list_manager_computes_daily_item_code_statistics(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Setup Customer
        MasterBusinessPartner::create([
            'bp_code' => 'CUST-HONDA',
            'bp_name' => 'PT ASTRA HONDA MOTOR',
            'bp_type' => 'Customer',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
        ]);

        // 2. Setup Master List Items:
        // - Item 1: Terhubung Customer
        MasterListItem::create([
            'item_code' => 'PART-CONN-01',
            'item_name' => 'Cover Front Connected',
            'customer_code' => 'CUST-HONDA',
        ]);

        // - Item 2: Belum Terhubung (Customer null)
        MasterListItem::create([
            'item_code' => 'PART-UNASS-02',
            'item_name' => 'Bracket Rear Unassigned',
            'customer_code' => null,
        ]);

        // - Item 3: Idle (tidak jalan hari ini)
        MasterListItem::create([
            'item_code' => 'PART-IDLE-03',
            'item_name' => 'Panel Side Idle',
            'customer_code' => 'CUST-HONDA',
        ]);

        // 3. Setup Daily Item Code untuk hari ini:
        // - PART-CONN-01 jalan di Mesin 1 Shift 1
        DailyItemCode::create([
            'user_id' => $this->user->id,
            'item_code' => 'PART-CONN-01',
            'quantity' => 1200,
            'shift' => 1,
            'schedule_date' => $today,
        ]);

        // - PART-UNASS-02 jalan di Mesin 1 Shift 2
        DailyItemCode::create([
            'user_id' => $this->user->id,
            'item_code' => 'PART-UNASS-02',
            'quantity' => 800,
            'shift' => 2,
            'schedule_date' => $today,
        ]);

        // 4. Test Livewire rendering & Stats
        Livewire::actingAs($this->user)
            ->test(MasterListManager::class)
            ->assertSee('Verifikasi Item Produksi (Daily Item Code)')
            ->assertSee('1 Belum Ada Customer')
            ->assertSee('Active DIC')
            ->assertSee('PART-CONN-01')
            ->assertSee('PART-UNASS-02')
            ->assertSee('PART-IDLE-03');
    }

    public function test_master_list_manager_scope_filter_dic_active(): void
    {
        $today = Carbon::today()->toDateString();

        MasterBusinessPartner::create([
            'bp_code' => 'CUST-YAMAHA',
            'bp_name' => 'PT YAMAHA INDONESIA',
            'bp_type' => 'Customer',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
        ]);

        MasterListItem::create([
            'item_code' => 'PART-DIC-RUNNING',
            'item_name' => 'Running Today Item',
            'customer_code' => 'CUST-YAMAHA',
        ]);

        MasterListItem::create([
            'item_code' => 'PART-NOT-RUNNING',
            'item_name' => 'Not Running Today',
            'customer_code' => 'CUST-YAMAHA',
        ]);

        DailyItemCode::create([
            'user_id' => $this->user->id,
            'item_code' => 'PART-DIC-RUNNING',
            'quantity' => 500,
            'shift' => 1,
            'schedule_date' => $today,
        ]);

        Livewire::actingAs($this->user)
            ->test(MasterListManager::class)
            ->set('filterScope', 'DIC_ACTIVE')
            ->assertSee('PART-DIC-RUNNING')
            ->assertDontSee('PART-NOT-RUNNING');
    }

    public function test_show_only_unassigned_dic_shortcut(): void
    {
        $today = Carbon::today()->toDateString();

        $cust = MasterBusinessPartner::create([
            'bp_code' => 'CUST-KAWASAKI',
            'bp_name' => 'PT KAWASAKI MOTOR',
            'bp_type' => 'Customer',
            'category' => MasterBusinessPartner::CATEGORY_CUSTOMER,
        ]);

        $itemConnected = MasterListItem::create([
            'item_code' => 'PART-KAWA-CONN',
            'item_name' => 'Kawasaki Connected Part',
            'customer_code' => 'CUST-KAWASAKI',
        ]);

        $itemUnassigned = MasterListItem::create([
            'item_code' => 'PART-KAWA-UNASS',
            'item_name' => 'Kawasaki Unassigned Part',
            'customer_code' => null,
        ]);

        DailyItemCode::create([
            'user_id' => $this->user->id,
            'item_code' => 'PART-KAWA-CONN',
            'quantity' => 1000,
            'shift' => 1,
            'schedule_date' => $today,
        ]);

        DailyItemCode::create([
            'user_id' => $this->user->id,
            'item_code' => 'PART-KAWA-UNASS',
            'quantity' => 500,
            'shift' => 1,
            'schedule_date' => $today,
        ]);

        // Call shortcut showOnlyUnassignedDic()
        $component = Livewire::actingAs($this->user)
            ->test(MasterListManager::class)
            ->call('showOnlyUnassignedDic')
            ->assertSet('filterScope', 'DIC_ACTIVE')
            ->assertSet('filterConnection', 'UNASSIGNED')
            ->assertSee('PART-KAWA-UNASS')
            ->assertDontSee('PART-KAWA-CONN');

        // Now map the customer code for PART-KAWA-UNASS
        $component->call('startEdit', $itemUnassigned->id, 'customer_code')
            ->set('editingValue', 'CUST-KAWASAKI')
            ->call('saveEdit');

        $this->assertEquals('CUST-KAWASAKI', $itemUnassigned->fresh()->customer_code);

        // Component should re-render and now show 100% complete
        $component->assertSee('100% Lengkap Terhubung');
    }
}
