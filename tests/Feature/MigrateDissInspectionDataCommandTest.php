<?php

namespace Tests\Feature;

use App\Models\Inspection\InspectionDefect;
use App\Models\Inspection\InspectionReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrateDissInspectionDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fails_gracefully_when_connection_is_unreachable(): void
    {
        Config::set('database.connections.invalid_diss_conn', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '59999', // Invalid port to ensure connection fails quickly
            'database' => 'non_existent_db',
            'username' => 'invalid_user',
            'password' => 'invalid_password',
        ]);

        $this->artisan('mes:migrate-diss-inspection-data', [
            '--diss-connection' => 'invalid_diss_conn',
        ])
        ->expectsOutputToContain('Cannot connect to source database')
        ->assertExitCode(1);
    }

    public function test_command_migrates_data_successfully_from_source_connection(): void
    {
        // Use default connection as the simulated source connection for testing
        $currentDefault = Config::get('database.default');
        Config::set('database.connections.mock_diss_conn', Config::get("database.connections.{$currentDefault}"));

        // Seed some data in target tables to test copy & idempotency
        InspectionDefect::create([
            'name' => 'Weld Line',
            'department' => 'INJECTION',
        ]);

        InspectionReport::create([
            'document_number' => 'INSP-SOURCE-001',
            'customer' => 'Customer A',
            'inspection_date' => '2026-10-19',
            'part_number' => 'PART-001',
            'part_name' => 'Widget',
            'weight' => 50.0,
            'weight_uom' => 'G',
            'material' => 'ABS',
            'color' => 'Black',
            'tool_number_or_cav_number' => '1',
            'machine_number' => 'M1',
            'shift' => 1,
            'inspector' => 'Inspector A',
        ]);

        // Run dry-run mode
        $this->artisan('mes:migrate-diss-inspection-data', [
            '--diss-connection' => 'mock_diss_conn',
            '--dry-run' => true,
        ])
        ->expectsOutputToContain('DRY-RUN')
        ->assertSuccessful();

        // Run live migration (idempotent upsert)
        $this->artisan('mes:migrate-diss-inspection-data', [
            '--diss-connection' => 'mock_diss_conn',
            '--chunk' => 10,
        ])
        ->expectsOutputToContain('[LIVE]')
        ->assertSuccessful();

        $this->assertDatabaseHas('inspection_defects', [
            'name' => 'Weld Line',
        ]);

        $this->assertDatabaseHas('inspection_reports', [
            'document_number' => 'INSP-SOURCE-001',
        ]);
    }
}
