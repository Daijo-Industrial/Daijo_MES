<?php

namespace App\Http\Controllers;

use App\Models\ProductionPayable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProductionPayableController extends Controller
{
    public function index()
    {
        $payables = ProductionPayable::latest('posting_date')->paginate(20);

        return view('production-payables.index', compact('payables'));
    }

    public function uploadForm()
    {
        return view('production-payables.upload');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
        ]);

        try {

            $file = $request->file('file');
            $filePath = $file->getRealPath();

            $data = $this->parseFile($filePath);

            if (empty($data)) {
                return back()->withErrors([
                    'file' => 'File tidak dapat dibaca atau kosong',
                ]);
            }

            $importedCount = 0;
            $skippedCount = 0;

            foreach ($data as $row) {

                try {

                    if (
                        empty($row['document number']) ||
                        empty($row['item no.'])
                    ) {
                        $skippedCount++;
                        continue;
                    }

                    $docNumber = trim($row['document number']);

                    $postingDate = $this->parseDate(
                        $row['posting date'] ?? ''
                    );

                    $valueDate = $this->parseDate(
                        $row['value date'] ?? ''
                    );

                    if (empty($postingDate)) {
                        $skippedCount++;
                        continue;
                    }

                    if (empty($valueDate)) {
                        $valueDate = $postingDate;
                    }

                    $itemNo = trim($row['item no.']);

                    $itemDesc = trim(
                        $row['item/service description'] ?? ''
                    );

                    $quantity = $this->parseQuantity(
                        $row['quantity'] ?? 0
                    );

                    $remarks = trim(
                        $row['remarks'] ?? ''
                    );

                    ProductionPayable::updateOrCreate(
                        [
                            'document_number' => $docNumber,
                        ],
                        [
                            'document_number' => $docNumber,
                            'posting_date' => $postingDate,
                            'value_date' => $valueDate,
                            'item_no' => $itemNo,
                            'item_description' => $itemDesc,
                            'quantity' => $quantity,
                            'remarks' => $remarks,
                            'status' => 'pending',
                            'uploaded_by' => auth()->id(),
                        ]
                    );

                    $importedCount++;

                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }
            }

            return back()->with(
                'success',
                "✅ Imported: {$importedCount} records | Skipped: {$skippedCount}"
            );

        } catch (\Exception $e) {

            return back()->withErrors([
                'file' => $e->getMessage(),
            ]);
        }
    }

    private function parseFile($filePath)
    {
        $data = [];

        $content = file_get_contents($filePath);

        // Handle UTF16 export SAP
        if (substr($content, 0, 2) === "\xFF\xFE") {
            $content = mb_convert_encoding(
                $content,
                'UTF-8',
                'UTF-16LE'
            );
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'sap_');

        file_put_contents(
            $tempFile,
            $content
        );

        $file = fopen($tempFile, 'r');

        $headerRow = null;

        while (($row = fgetcsv($file, 0, "\t")) !== false) {

            if ($headerRow === null) {

                $headerRow = array_map(
                    fn ($value) => strtolower(trim($value)),
                    $row
                );

                continue;
            }

            if (count(array_filter($row)) === 0) {
                continue;
            }

            $mappedRow = [];

            foreach ($headerRow as $index => $columnName) {

                $mappedRow[$columnName] =
                    $row[$index] ?? null;
            }

            $data[] = $mappedRow;
        }

        fclose($file);

        unlink($tempFile);

        return $data;
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return;
        }

        try {

            // Excel serial date
            if (is_numeric($date)) {

                return ExcelDate::excelToDateTimeObject($date)
                    ->format('Y-m-d');
            }

            $date = trim((string) $date);

            // SAP 08.06.26
            if (preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $date)) {

                return Carbon::createFromFormat(
                    'd.m.y',
                    $date
                )->format('Y-m-d');
            }

            // SAP 08.06.2026
            if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {

                return Carbon::createFromFormat(
                    'd.m.Y',
                    $date
                )->format('Y-m-d');
            }

            return Carbon::parse($date)
                ->format('Y-m-d');

        } catch (\Exception $e) {

            return;
        }
    }

    private function parseQuantity($qty)
    {
        if ($qty === null || $qty === '') {
            return 0;
        }

        $qty = str_replace(',', '', (string) $qty);

        return (int) round((float) $qty);
    }

    public function show($id)
    {
        $payable = ProductionPayable::findOrFail($id);

        return view(
            'production-payables.show',
            compact('payable')
        );
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,received,invoiced,paid,cancelled',
        ]);

        $payable = ProductionPayable::findOrFail($id);

        $payable->update([
            'status' => $request->status,
        ]);

        return back()->with(
            'success',
            'Status updated'
        );
    }

    public function destroy($id)
    {
        ProductionPayable::findOrFail($id)->delete();

        return back()->with(
            'success',
            'Record deleted'
        );
    }
}
