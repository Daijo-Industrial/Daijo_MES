<?php

namespace Tests\Unit;

use App\Exports\SoDataExport;
use App\Imports\SoImport;
use App\Models\SoData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class SoImportTrailingDotTest extends TestCase
{
    use RefreshDatabase;

    public function test_so_import_custom_value_binder_preserves_trailing_dot(): void
    {
        $import = new SoImport;
        $spreadsheet = new Spreadsheet;
        $cell = $spreadsheet->getActiveSheet()->getCell('A1');

        $import->bindValue($cell, '31099125084.');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame('31099125084.', $cell->getValue());
    }

    public function test_so_data_export_custom_value_binder_preserves_trailing_dot(): void
    {
        $export = new SoDataExport([]);
        $spreadsheet = new Spreadsheet;
        $cell = $spreadsheet->getActiveSheet()->getCell('A1');

        $export->bindValue($cell, '31099125084.');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertSame('31099125084.', $cell->getValue());
    }

    public function test_so_import_model_creates_so_data_with_exact_trailing_dot(): void
    {
        $import = new SoImport;
        $row = [
            'DOC-DOT-001',
            'CUSTOMER PT TEST',
            '2026-11-24',
            '31099125084.',
            'COVER FRONT BODY VP6916',
            100,
            'PCS',
            10,
            'BOX',
            '2026-11-24',
            '2026-11-24',
            123456,
        ];

        $model = $import->model($row);
        $this->assertInstanceOf(SoData::class, $model);
        $this->assertSame('31099125084.', $model->item_code);
        $this->assertNotSame('31099125084', $model->item_code);

        $model->save();

        $persisted = SoData::where('doc_num', 'DOC-DOT-001')->first();
        $this->assertNotNull($persisted);
        $this->assertSame('31099125084.', $persisted->item_code);
    }

    public function test_csv_round_trip_preserves_trailing_dot(): void
    {
        $testData = [
            [
                'DOC-DOT-002',
                'CUSTOMER PT TEST',
                '2026-11-24',
                '31099125084.',
                'COVER FRONT BODY VP6916',
                50,
                'PCS',
                5,
                'BOX',
                '2026-11-24',
                '2026-11-24',
                123456,
            ],
        ];

        $csvPath = 'public/test_dot_roundtrip.csv';
        Excel::store(new SoDataExport($testData), $csvPath);

        $importedRows = Excel::toArray(new SoImport, storage_path('app/' . $csvPath))[0];
        $this->assertSame('31099125084.', (string) $importedRows[0][3]);

        if (file_exists(storage_path('app/' . $csvPath))) {
            unlink(storage_path('app/' . $csvPath));
        }
    }
}
