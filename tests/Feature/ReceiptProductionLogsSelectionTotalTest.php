<?php

namespace Tests\Feature;

use App\Livewire\ReceiptProductionLogs;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ReceiptProductionLogsSelectionTotalTest extends TestCase
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
        Schema::create('production_scanned_data', function (Blueprint $table) {
            $table->id();
            $table->string('spk_code');
            $table->string('warehouse');
            $table->integer('quantity');
            $table->string('item_code')->nullable();
            $table->string('label')->nullable();
            $table->string('user')->nullable();
            $table->boolean('processed')->default(false);
            $table->unsignedBigInteger('summary_id')->nullable();
            $table->timestamps();
        });

        Schema::create('production_summary', function (Blueprint $table) {
            $table->id();
            $table->string('spk_code');
            $table->integer('total_quantity');
            $table->string('warehouse');
            $table->string('label')->nullable();
            $table->date('created_date');
            $table->integer('sap_sent')->default(0);
            $table->timestamp('sap_sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_selected_spk_calculates_total_quantity_in_real_time(): void
    {
        $today = now()->timezone('Asia/Jakarta')->format('Y-m-d');

        $id1 = DB::table('production_summary')->insertGetId([
            'spk_code' => 'SPK-001',
            'total_quantity' => 120,
            'warehouse' => 'FFI',
            'label' => 'L1',
            'created_date' => $today,
            'sap_sent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id2 = DB::table('production_summary')->insertGetId([
            'spk_code' => 'SPK-002',
            'total_quantity' => 380,
            'warehouse' => 'FFI',
            'label' => 'L2',
            'created_date' => $today,
            'sap_sent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $id3 = DB::table('production_summary')->insertGetId([
            'spk_code' => 'SPK-003',
            'total_quantity' => 500,
            'warehouse' => 'FFI',
            'label' => 'L3',
            'created_date' => $today,
            'sap_sent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(ReceiptProductionLogs::class)
            ->assertSet('selectedSummary.count', 0)
            ->assertSet('selectedSummary.total_qty', 0)
            // Select SPK 1 and SPK 2 -> 120 + 380 = 500 pcs
            ->set('selectedLogs', [(string) $id1, (string) $id2])
            ->assertSet('selectedSummary.count', 2)
            ->assertSet('selectedSummary.total_qty', 500)
            ->assertSee('2')
            ->assertSee('500 pcs')
            ->assertSee('Total Akan Dikirim:')
            // Add SPK 3 -> 500 + 500 = 1,000 pcs
            ->set('selectedLogs', [(string) $id1, (string) $id2, (string) $id3])
            ->assertSet('selectedSummary.count', 3)
            ->assertSet('selectedSummary.total_qty', 1000)
            ->assertSee('1,000 pcs')
            // Deselect all
            ->set('selectedLogs', [])
            ->assertSet('selectedSummary.count', 0)
            ->assertSet('selectedSummary.total_qty', 0);
    }
}
