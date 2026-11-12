<?php

namespace Tests\Feature;

use App\Livewire\DeliveryScheduleForm;
use App\Models\MasterCustomerDelivery;
use App\Models\MasterDeliverySchedule;
use App\Models\MasterListItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class DeliveryScheduleFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('users', 'last_activity_at')) {
            Schema::table('users', function ($table) {
                $table->timestamp('last_activity_at')->nullable();
            });
        }

        if (! Schema::hasTable('master_customer_deliveries')) {
            Schema::create('master_customer_deliveries', function (Blueprint $table) {
                $table->id();
                $table->string('customer_code')->unique();
                $table->string('customer_name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('master_list_items')) {
            Schema::create('master_list_items', function (Blueprint $table) {
                $table->id();
                $table->string('item_code')->unique();
                $table->string('item_name')->nullable();
                $table->string('customer_code')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('master_delivery_schedule')) {
            Schema::create('master_delivery_schedule', function (Blueprint $table) {
                $table->id();
                $table->string('customer_code');
                $table->string('item_code');
                $table->date('tanggal');
                $table->integer('quantity');
                $table->string('so_num')->nullable();
                $table->timestamps();
            });
        }

        $role = Role::firstOrCreate(['name' => 'SUPER-ADMIN']);
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'name' => 'Testing User',
        ]);
    }

    public function test_page_renders_with_upload_excel_button(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeliveryScheduleForm::class)
            ->assertStatus(200)
            ->assertSee('Input Delivery Schedule')
            ->assertSee('Upload Excel')
            ->assertSet('showUploadModal', false)
            ->call('openUploadModal')
            ->assertSet('showUploadModal', true)
            ->assertSee('Upload Delivery Schedule dari Excel / CSV')
            ->call('closeUploadModal')
            ->assertSet('showUploadModal', false);
    }

    public function test_user_can_download_excel_and_csv_templates(): void
    {
        Livewire::actingAs($this->user)
            ->test(DeliveryScheduleForm::class)
            ->call('downloadTemplate')
            ->assertFileDownloaded('template_delivery_schedule.xlsx');

        Livewire::actingAs($this->user)
            ->test(DeliveryScheduleForm::class)
            ->call('downloadTemplateCsv')
            ->assertFileDownloaded('template_delivery_schedule.csv');
    }

    public function test_user_can_upload_excel_in_list_format_and_save(): void
    {
        // Setup master customer & item
        MasterCustomerDelivery::create([
            'customer_code' => 'D0000047',
            'customer_name' => 'PT CUSTOMER TESTING',
        ]);
        MasterListItem::create([
            'item_code' => '401-27181M650A',
            'item_name' => 'DOOR VENT RHD',
            'customer_code' => 'D0000047',
            'tipe_mesin' => 'INJECTION',
            'standart_packaging_list' => 1,
        ]);

        // Generate temporary Excel file in List format
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Customer Code');
        $sheet->setCellValue('B1', 'Item Code');
        $sheet->setCellValue('C1', 'Delivery Date');
        $sheet->setCellValue('D1', 'Quantity');
        $sheet->setCellValue('E1', 'SO Number');

        $sheet->setCellValue('A2', 'D0000047');
        $sheet->setCellValue('B2', '401-27181M650A');
        $sheet->setCellValue('C2', '2026-11-15');
        $sheet->setCellValue('D2', 500);
        $sheet->setCellValue('E2', 'SO-TEST-01');

        $sheet->setCellValue('A3', 'D0000047');
        $sheet->setCellValue('B3', '401-27181M650A');
        $sheet->setCellValue('C3', '2026-11-20');
        $sheet->setCellValue('D3', 350);
        $sheet->setCellValue('E3', 'SO-TEST-01');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_upload_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'test_schedule.xlsx',
            file_get_contents($tempPath)
        );

        Livewire::actingAs($this->user)
            ->test(DeliveryScheduleForm::class)
            ->call('openUploadModal')
            ->set('uploadFile', $uploadedFile)
            ->assertSet('previewSummary.total_rows', 2)
            ->assertSet('previewSummary.total_qty', 850)
            ->call('processImport')
            ->assertSet('showUploadModal', false);

        $this->assertDatabaseHas('master_delivery_schedule', [
            'customer_code' => 'D0000047',
            'item_code' => '401-27181M650A',
            'tanggal' => '2026-11-15',
            'quantity' => 500,
            'so_num' => 'SO-TEST-01',
        ]);

        $this->assertDatabaseHas('master_delivery_schedule', [
            'customer_code' => 'D0000047',
            'item_code' => '401-27181M650A',
            'tanggal' => '2026-11-20',
            'quantity' => 350,
            'so_num' => 'SO-TEST-01',
        ]);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }

    public function test_user_can_upload_excel_in_matrix_format(): void
    {
        MasterCustomerDelivery::create([
            'customer_code' => 'D0000047',
            'customer_name' => 'PT CUSTOMER TESTING',
        ]);
        MasterListItem::create([
            'item_code' => 'PART-MATRIX-01',
            'item_name' => 'Matrix Part Sample',
            'customer_code' => 'D0000047',
            'tipe_mesin' => 'INJECTION',
            'standart_packaging_list' => 1,
        ]);

        // Generate matrix format: Item Code, Customer Code, and day columns 1, 2, 3
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Customer Code');
        $sheet->setCellValue('B1', 'Item Code');
        $sheet->setCellValue('C1', '1');
        $sheet->setCellValue('D1', '2');
        $sheet->setCellValue('E1', '3');

        $sheet->setCellValue('A2', 'D0000047');
        $sheet->setCellValue('B2', 'PART-MATRIX-01');
        $sheet->setCellValue('C2', 100);
        $sheet->setCellValue('D2', 0); // 0 should be skipped
        $sheet->setCellValue('E2', 250);

        $tempPath = tempnam(sys_get_temp_dir(), 'test_matrix_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $uploadedFile = UploadedFile::fake()->createWithContent(
            'test_matrix.xlsx',
            file_get_contents($tempPath)
        );

        Livewire::actingAs($this->user)
            ->test(DeliveryScheduleForm::class)
            ->call('openUploadModal')
            ->set('uploadMonth', 11)
            ->set('uploadYear', 2026)
            ->set('uploadFile', $uploadedFile)
            ->assertSet('previewSummary.total_rows', 2)
            ->assertSet('previewSummary.total_qty', 350)
            ->call('processImport');

        $this->assertDatabaseHas('master_delivery_schedule', [
            'customer_code' => 'D0000047',
            'item_code' => 'PART-MATRIX-01',
            'tanggal' => '2026-11-01',
            'quantity' => 100,
        ]);

        $this->assertDatabaseHas('master_delivery_schedule', [
            'customer_code' => 'D0000047',
            'item_code' => 'PART-MATRIX-01',
            'tanggal' => '2026-11-03',
            'quantity' => 250,
        ]);

        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
    }
}

