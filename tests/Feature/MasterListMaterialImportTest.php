<?php

namespace Tests\Feature;

use App\Imports\MasterListMaterialImport;
use App\Livewire\MaterialWarehouse\MasterListMaterialIndex;
use App\Models\MasterListMaterial;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MasterListMaterialImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestSchema();
    }

    private function createTestSchema(): void
    {
        Schema::dropIfExists('master_list_materials');
        Schema::create('master_list_materials', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('item_code', 100)->unique();
            $table->text('item_description')->nullable();
            $table->string('preferred_supplier', 100)->nullable();
            $table->string('purchasing_uom', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->foreignId('role_id')->nullable();
                $table->string('password')->default('password');
                $table->timestamps();
            });
        }
    }

    public function test_can_create_and_query_master_list_material()
    {
        $material = MasterListMaterial::create([
            'item_code' => '180-CN890-FL',
            'item_description' => 'SPK CORD 22AWG 3M TR TUBE W-B',
            'preferred_supplier' => null,
            'purchasing_uom' => 'PCS',
        ]);

        $this->assertDatabaseHas('master_list_materials', [
            'item_code' => '180-CN890-FL',
            'purchasing_uom' => 'PCS',
        ]);

        $this->assertNull($material->preferred_supplier);
    }

    public function test_import_collection_upsert_and_null_conversion()
    {
        $rows = new Collection([
            [
                'item_no' => '180-CN890-FL',
                'item_description' => 'SPK CORD 22AWG 3M TR TUBE W-B',
                'preferred_supplier' => '', // should be converted to null
                'purchasing_uom' => 'PCS',
            ],
            [
                'item_no' => '182-BEATS-SPK',
                'item_description' => 'SPEAKER WIRE 2-PIN L-95mm',
                'preferred_supplier' => 'VMI0000561',
                'purchasing_uom' => 'PCS',
            ],
        ]);

        $import = new MasterListMaterialImport('test_batch_123');
        $import->collection($rows);

        $this->assertDatabaseHas('master_list_materials', [
            'item_code' => '180-CN890-FL',
            'preferred_supplier' => null,
        ]);

        $this->assertDatabaseHas('master_list_materials', [
            'item_code' => '182-BEATS-SPK',
            'preferred_supplier' => 'VMI0000561',
        ]);
    }

    public function test_can_render_master_list_material_index_component()
    {
        $role = \App\Models\Role::firstOrCreate(['name' => 'ADMIN']);
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin_' . uniqid() . '@example.com',
            'role_id' => $role->id,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        Livewire::test(MasterListMaterialIndex::class)
            ->assertStatus(200)
            ->assertSee('Master List Material');
    }
}
