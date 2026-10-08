<?php

namespace App\Livewire\Admin;

use App\Models\MasterBusinessPartner;
use App\Models\MasterCustomerDelivery;
use App\Models\MasterItemLog;
use App\Models\MasterListItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MasterListManager extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $hardSync = false;
    public string $filterConnection = 'ALL'; // 'ALL', 'CONNECTED', 'UNASSIGNED'

    // Excel Import status
    public $tempFilePath = '';
    public $totalRows = 0;
    public $previewRows = [];
    public $importedRowsCount = 0;
    public $importing = false;

    // Inline edit state
    public $editingItemId = null;
    public $editingField = null;
    public $editingValue = '';

    // File Upload binding
    public $file;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterConnection' => ['except' => 'ALL'],
    ];

    public function updatingSearch()
    {
        $this->resetPage('itemsPage');
    }

    public function setConnectionFilter(string $filter): void
    {
        $this->filterConnection = $filter;
        $this->resetPage('itemsPage');
    }

    public function startEdit($itemId, $field)
    {
        $item = MasterListItem::findOrFail($itemId);
        $this->editingItemId = $itemId;
        $this->editingField = $field;
        $this->editingValue = $item->$field;
    }

    public function cancelEdit()
    {
        $this->editingItemId = null;
        $this->editingField = null;
        $this->editingValue = '';
    }

    public function saveEdit()
    {
        $item = MasterListItem::findOrFail($this->editingItemId);
        $field = $this->editingField;

        $rules = [
            'tipe_mesin' => 'nullable|string',
            'standart_packaging_list' => 'nullable|integer|min:0',
            'setup_time_minute' => 'nullable|integer|min:0',
            'pair' => 'nullable|integer|min:0',
            'cavity' => 'nullable|integer|min:0',
            'cycle_time' => 'nullable|integer|min:0',
            'customer_code' => 'nullable|string|max:255',
            'project_code' => 'nullable|string|max:255',
            'family' => 'nullable|string|max:255',
            'description_in_foreign_lang' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'half_code_1' => 'nullable|string|max:255',
            'half_code_2' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ];

        $validated = $this->validate([
            'editingValue' => $rules[$field] ?? 'nullable',
        ]);

        $oldValue = $item->$field;
        $newValue = $this->editingValue;

        if ($field === 'customer_code') {
            $newValue = trim((string) $newValue);
            if ($newValue === '' || $newValue === '-' || $newValue === 'null') {
                $newValue = '0';
            }
        }

        if ($oldValue != $newValue) {
            $item->$field = $newValue;
            $item->save();

            // Log the change
            MasterItemLog::create([
                'user_id' => auth()->id(),
                'item_code' => $item->item_code,
                'action' => 'inline_edit',
                'old_values' => [$field => $oldValue],
                'new_values' => [$field => $newValue],
            ]);

            session()->flash('message', "Item {$item->item_code} updated successfully.");
        }

        $this->cancelEdit();
    }

    private function parseNumeric($val, $default = 0)
    {
        if ($val === null || $val === '') {
            return $default;
        }
        if (is_numeric($val)) {
            return (int) round((float) $val);
        }
        $str = trim((string) $val);
        if ($str === '' || $str === '-') {
            return $default;
        }
        // Handle thousands separators formatted like 6.000.000 or 1,600,000
        if (substr_count($str, '.') > 1) {
            $str = str_replace('.', '', $str);
            if (strlen($str) > 3 && substr($str, -3) === '000') {
                $str = substr($str, 0, -3);
            }
        } elseif (substr_count($str, ',') > 1) {
            $str = str_replace(',', '', $str);
            if (strlen($str) > 3 && substr($str, -3) === '000') {
                $str = substr($str, 0, -3);
            }
        } else {
            $clean = str_replace(',', '', $str);
            if (is_numeric($clean)) {
                return (int) round((float) $clean);
            }
        }
        return is_numeric($str) ? (int) round((float) $str) : $default;
    }

    private function parseString($val, $default = '0')
    {
        if ($val === null) {
            return $default;
        }
        $str = trim((string) $val);
        return $str === '' ? $default : $str;
    }

    public function updatedFile()
    {
        $this->validate([
            'file' => 'required|file|mimes:xls,xlsx,csv,txt|max:20480', // 20MB limit
        ]);

        try {
            $realPath = $this->file->getRealPath();

            // Load spreadsheet using PhpSpreadsheet
            $spreadsheet = IOFactory::load($realPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row if present
            if (!empty($rows) && (
                strpos(strtolower($rows[0][1] ?? ''), 'item') !== false || 
                strpos(strtolower($rows[0][0] ?? ''), '#') !== false
            )) {
                array_shift($rows);
            }

            $mappedRows = [];
            foreach ($rows as $row) {
                // Ensure item_code is present (column index 1)
                $itemCode = isset($row[1]) ? trim((string) $row[1]) : '';
                if (empty($itemCode)) {
                    continue;
                }

                // Cycle time calculation: Column 9 (Standard Time) * 60, rounded down
                $cycleTimeRaw = isset($row[9]) ? $row[9] : 0;
                $cycleTime = (int) floor(floatval(str_replace(',', '.', (string) $cycleTimeRaw)) * 60);

                $mappedRows[] = [
                    'item_code' => $itemCode,
                    'item_name' => $this->parseString($row[2] ?? '', ''),
                    'tipe_mesin' => $this->parseString($row[3] ?? '', '0'),
                    'standart_packaging_list' => $this->parseNumeric($row[4] ?? 0, 0),
                    'setup_time_minute' => $this->parseNumeric($row[5] ?? 0, 0),
                    'pair' => $this->parseNumeric($row[6] ?? 0, 0),
                    'cavity' => $this->parseNumeric($row[7] ?? 0, 0),
                    'customer_code' => $this->parseString($row[8] ?? '', '0'),
                    'cycle_time' => $cycleTime,
                    'family' => $this->parseString($row[10] ?? '', '0'),
                    'description_in_foreign_lang' => $this->parseString($row[11] ?? '', '0'),
                    'color' => $this->parseString($row[12] ?? '', '0'),
                    'half_code_1' => $this->parseString($row[13] ?? '', '0'),
                    'half_code_2' => $this->parseString($row[14] ?? '', '0'),
                    'position' => $this->parseString($row[15] ?? '', '0'),
                ];
            }

            // Write mapped rows to a temporary JSON file to avoid keeping large arrays in session/state
            $jsonFileName = 'mapped_import_' . time() . '_' . uniqid() . '.json';
            $jsonDirectory = storage_path('app/temp');
            if (!file_exists($jsonDirectory)) {
                mkdir($jsonDirectory, 0755, true);
            }
            
            $jsonPath = $jsonDirectory . '/' . $jsonFileName;
            file_put_contents($jsonPath, json_encode($mappedRows));

            $this->tempFilePath = $jsonPath;
            $this->totalRows = count($mappedRows);
            $this->previewRows = array_slice($mappedRows, 0, 5);
            $this->importedRowsCount = 0;

        } catch (\Throwable $e) {
            Log::error('Upload error', ['error' => $e->getMessage()]);
            session()->flash('error', 'Error reading Excel/CSV file: ' . $e->getMessage());
        }
    }

    public function startImport()
    {
        $this->importedRowsCount = 0;
        $this->importing = true;
    }

    public function importChunk()
    {
        if (!$this->tempFilePath || !file_exists($this->tempFilePath)) {
            $this->importing = false;
            return;
        }

        $allRows = json_decode(file_get_contents($this->tempFilePath), true);
        if (!$allRows) {
            $this->importing = false;
            return;
        }

        $offset = $this->importedRowsCount;
        $chunkSize = 500;
        $chunk = array_slice($allRows, $offset, $chunkSize);
        $processed = 0;

        $fieldsToTrack = [
            'item_name', 'tipe_mesin', 'standart_packaging_list', 'setup_time_minute',
            'pair', 'cavity', 'customer_code', 'cycle_time',
            'family', 'description_in_foreign_lang', 'color', 'half_code_1', 'half_code_2', 'position'
        ];

        DB::transaction(function () use ($chunk, &$processed, $fieldsToTrack) {
            foreach ($chunk as $data) {
                if (empty($data['item_code'])) {
                    $processed++;
                    continue;
                }

                $item = MasterListItem::where('item_code', $data['item_code'])->first();

                if ($item) {
                    // Update item (Upsert)
                    $oldValues = $item->only($fieldsToTrack);
                    
                    // SAP Owned fields are always updated
                    $item->item_name = $data['item_name'] ?? $item->item_name;
                    $item->customer_code = $data['customer_code'] ?? $item->customer_code;
                    $item->family = $data['family'] ?? $item->family;
                    $item->description_in_foreign_lang = $data['description_in_foreign_lang'] ?? $item->description_in_foreign_lang;
                    $item->color = $data['color'] ?? $item->color;
                    $item->half_code_1 = $data['half_code_1'] ?? $item->half_code_1;
                    $item->half_code_2 = $data['half_code_2'] ?? $item->half_code_2;
                    $item->position = $data['position'] ?? $item->position;

                    // MES Owned fields are only updated if hardSync is enabled
                    if ($this->hardSync) {
                        $item->tipe_mesin = isset($data['tipe_mesin']) ? $data['tipe_mesin'] : $item->tipe_mesin;
                        $item->standart_packaging_list = isset($data['standart_packaging_list']) ? (int)$data['standart_packaging_list'] : $item->standart_packaging_list;
                        $item->setup_time_minute = isset($data['setup_time_minute']) ? (int)$data['setup_time_minute'] : $item->setup_time_minute;
                        $item->pair = isset($data['pair']) ? (int)$data['pair'] : $item->pair;
                        $item->cavity = isset($data['cavity']) ? (int)$data['cavity'] : $item->cavity;
                        $item->cycle_time = isset($data['cycle_time']) ? (int)$data['cycle_time'] : $item->cycle_time;
                    }

                    if ($item->isDirty()) {
                        $newValues = $item->only($fieldsToTrack);
                        $item->save();

                        // Log differences
                        $diffOld = [];
                        $diffNew = [];
                        foreach ($newValues as $k => $v) {
                            if (($oldValues[$k] ?? null) != $v) {
                                $diffOld[$k] = $oldValues[$k] ?? null;
                                $diffNew[$k] = $v;
                            }
                        }

                        if (!empty($diffNew)) {
                            MasterItemLog::create([
                                'user_id' => auth()->id(),
                                'item_code' => $item->item_code,
                                'action' => 'excel_import_update',
                                'old_values' => $diffOld,
                                'new_values' => $diffNew,
                            ]);
                        }
                    }
                } else {
                    // Create new item
                    $item = new MasterListItem();
                    $item->item_code = $data['item_code'];
                    $item->item_name = $data['item_name'] ?? '';
                    $item->tipe_mesin = $data['tipe_mesin'] ?? '0';
                    $item->standart_packaging_list = (int)($data['standart_packaging_list'] ?? 0);
                    $item->setup_time_minute = (int)($data['setup_time_minute'] ?? 0);
                    $item->pair = (int)($data['pair'] ?? 0);
                    $item->cavity = (int)($data['cavity'] ?? 0);
                    $item->customer_code = $data['customer_code'] ?? '0';
                    $item->cycle_time = (int)($data['cycle_time'] ?? 0);
                    $item->project_code = '0';
                    $item->family = $data['family'] ?? '0';
                    $item->description_in_foreign_lang = $data['description_in_foreign_lang'] ?? '0';
                    $item->color = $data['color'] ?? '0';
                    $item->half_code_1 = $data['half_code_1'] ?? '0';
                    $item->half_code_2 = $data['half_code_2'] ?? '0';
                    $item->position = $data['position'] ?? '0';
                    $item->save();

                    MasterItemLog::create([
                        'user_id' => auth()->id(),
                        'item_code' => $item->item_code,
                        'action' => 'excel_import_create',
                        'old_values' => null,
                        'new_values' => $item->only(array_merge(['item_code'], $fieldsToTrack)),
                    ]);
                }

                $processed++;
            }
        });

        $this->importedRowsCount += $processed;

        if ($this->importedRowsCount >= $this->totalRows) {
            // Clean up file
            if (file_exists($this->tempFilePath)) {
                unlink($this->tempFilePath);
            }
            $this->tempFilePath = '';
            $this->importing = false;
            $this->file = null;
            $this->totalRows = 0;
            $this->previewRows = [];
            $this->importedRowsCount = 0;
            session()->flash('message', "Successfully synced records from SAP Excel.");
        }
    }

    public function cancelImport()
    {
        if ($this->tempFilePath && file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
        }
        $this->tempFilePath = '';
        $this->totalRows = 0;
        $this->previewRows = [];
        $this->importedRowsCount = 0;
        $this->importing = false;
        $this->file = null;
    }

    public function render()
    {
        $query = MasterListItem::with(['customer', 'businessPartner'])
            ->where(function ($q) {
                $q->where('item_code', 'like', '%'.$this->search.'%')
                    ->orWhere('item_name', 'like', '%'.$this->search.'%')
                    ->orWhere('customer_code', 'like', '%'.$this->search.'%');
            });

        if ($this->filterConnection === 'CONNECTED') {
            $query->whereNotNull('customer_code')
                ->whereNotIn('customer_code', ['', '0', '-']);
        } elseif ($this->filterConnection === 'UNASSIGNED') {
            $query->where(function ($q) {
                $q->whereNull('customer_code')
                    ->orWhereIn('customer_code', ['', '0', '-']);
            });
        }

        $items = $query->orderBy('id', 'desc')->paginate(15, ['*'], 'itemsPage');

        $logs = MasterItemLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'logsPage');

        $counts = [
            'ALL' => MasterListItem::count(),
            'CONNECTED' => MasterListItem::whereNotNull('customer_code')->whereNotIn('customer_code', ['', '0', '-'])->count(),
            'UNASSIGNED' => MasterListItem::where(function ($q) {
                $q->whereNull('customer_code')->orWhereIn('customer_code', ['', '0', '-']);
            })->count(),
        ];

        // Customers list for dropdown selector
        $customers = MasterBusinessPartner::customers()
            ->orderBy('bp_name')
            ->get(['bp_code as customer_code', 'bp_name as customer_name', 'type']);
        if ($customers->isEmpty()) {
            $customers = MasterCustomerDelivery::orderBy('customer_name')->get();
        }

        return view('livewire.admin.master-list-manager', [
            'items' => $items,
            'logs' => $logs,
            'counts' => $counts,
            'customers' => $customers,
        ]);
    }
}
