<?php

namespace App\Console\Commands;

use App\Models\MasterMould;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ImportMasterMouldsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mould:import-master {file? : Optional path to Excel file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import master moulds catalogue from LIST MOULD Excel file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file') ?: base_path('LIST MOULD 1507.xlsx');

        if (! file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Loading Excel file: {$filePath} ...");
        ini_set('memory_limit', '1024M');

        $reader = new Xlsx();
        $reader->setReadDataOnly(true);

        // Check sheet names
        $worksheetInfo = $reader->listWorksheetInfo($filePath);
        $targetSheet = 'Sheet3 (3)';
        $found = false;
        foreach ($worksheetInfo as $info) {
            if ($info['worksheetName'] === $targetSheet) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            // fallback to first sheet if Sheet3 (3) is not present
            $targetSheet = $worksheetInfo[0]['worksheetName'];
        }

        $reader->setLoadSheetsOnly([$targetSheet]);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getSheetByName($targetSheet);

        $highestRow = $sheet->getHighestRow();
        $this->info("Parsing sheet '{$targetSheet}' with {$highestRow} rows ...");

        $imported = 0;
        $updated = 0;
        $bar = $this->output->createProgressBar($highestRow - 5);
        $bar->start();

        for ($r = 6; $r <= $highestRow; $r++) {
            $mouldNo = trim((string) $sheet->getCell("J{$r}")->getValue());
            $partNo = trim((string) $sheet->getCell("E{$r}")->getValue());
            $partName = trim((string) $sheet->getCell("F{$r}")->getValue());
            $customer = trim((string) $sheet->getCell("I{$r}")->getValue());
            $mcNo = trim((string) $sheet->getCell("B{$r}")->getValue());
            $material = trim((string) $sheet->getCell("G{$r}")->getValue());

            if (empty($mouldNo) && empty($partNo)) {
                $bar->advance();
                continue;
            }

            // Fallback mould_no if missing
            if (empty($mouldNo)) {
                $mouldNo = 'MLD-' . ($partNo ?: 'UNKNOWN-' . $r);
            }

            if (empty($partNo)) {
                $partNo = $mouldNo;
            }

            if (empty($partName)) {
                $partName = $partNo;
            }

            if (empty($customer)) {
                $customer = 'N/A';
            }

            // Extract model if in part name e.g. 5H45 or 4L45W
            $model = null;
            if (preg_match('/(?:5H45|4L45W|5L45W|26MY|[A-Z0-9]{3,6})/i', $customer . ' ' . $partName, $matches)) {
                $model = trim($matches[0]);
            }

            $mould = MasterMould::updateOrCreate(
                [
                    'mould_no' => $mouldNo,
                    'part_no' => $partNo,
                ],
                [
                    'part_name' => $partName,
                    'model' => $model,
                    'customer' => $customer,
                    'machine_number' => $mcNo ?: null,
                    'material' => $material ?: null,
                    'is_active' => true,
                ]
            );

            if ($mould->wasRecentlyCreated) {
                $imported++;
            } else {
                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Import completed! New moulds: {$imported}, Updated: {$updated}, Total in database: " . MasterMould::count());

        return Command::SUCCESS;
    }
}
