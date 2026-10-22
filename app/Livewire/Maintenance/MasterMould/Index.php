<?php

namespace App\Livewire\Maintenance\MasterMould;

use App\Models\MasterMould;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class Index extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $selectedCustomer = '';

    // Modal state for Add / Edit
    public $showFormModal = false;
    public $isEdit = false;
    public $mouldId = null;

    public $mould_no = '';
    public $part_no = '';
    public $part_name = '';
    public $model = '';
    public $customer = '';
    public $machine_number = '';
    public $material = '';
    public $is_active = true;

    // Excel Upload modal state
    public $showUploadModal = false;
    public $excelFile = null;
    public $isUploading = false;

    protected $paginationTheme = 'tailwind';

    protected function rules()
    {
        return [
            'mould_no' => 'required|string|max:100',
            'part_no' => 'required|string|max:100',
            'part_name' => 'required|string|max:255',
            'customer' => 'required|string|max:150',
            'model' => 'nullable|string|max:100',
            'machine_number' => 'nullable|string|max:100',
            'material' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedCustomer()
    {
        $this->resetPage();
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->showFormModal = true;
    }

    public function openEditModal($id)
    {
        $mould = MasterMould::findOrFail($id);
        $this->mouldId = $mould->id;
        $this->mould_no = $mould->mould_no;
        $this->part_no = $mould->part_no;
        $this->part_name = $mould->part_name;
        $this->model = $mould->model;
        $this->customer = $mould->customer;
        $this->machine_number = $mould->machine_number;
        $this->material = $mould->material;
        $this->is_active = (bool) $mould->is_active;

        $this->isEdit = true;
        $this->showFormModal = true;
    }

    public function closeFormModal()
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->mouldId = null;
        $this->mould_no = '';
        $this->part_no = '';
        $this->part_name = '';
        $this->model = '';
        $this->customer = '';
        $this->machine_number = '';
        $this->material = '';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function saveMould()
    {
        $this->validate();

        MasterMould::updateOrCreate(
            ['id' => $this->mouldId],
            [
                'mould_no' => mb_strtoupper(trim($this->mould_no)),
                'part_no' => trim($this->part_no),
                'part_name' => trim($this->part_name),
                'model' => $this->model ? trim($this->model) : null,
                'customer' => mb_strtoupper(trim($this->customer)),
                'machine_number' => $this->machine_number ? trim($this->machine_number) : null,
                'material' => $this->material ? trim($this->material) : null,
                'is_active' => $this->is_active,
            ]
        );

        $this->closeFormModal();
        session()->flash('success', $this->isEdit ? 'Mould berhasil diperbarui!' : 'Mould baru berhasil ditambahkan!');
    }

    public function deleteMould($id)
    {
        $mould = MasterMould::findOrFail($id);
        $mould->delete();
        session()->flash('success', 'Mould berhasil dihapus!');
    }

    public function openUploadModal()
    {
        $this->excelFile = null;
        $this->showUploadModal = true;
    }

    public function closeUploadModal()
    {
        $this->showUploadModal = false;
        $this->excelFile = null;
    }

    public function uploadExcel()
    {
        $this->validate([
            'excelFile' => 'required|file|mimes:xlsx,xls|max:20480',
        ]);

        $this->isUploading = true;

        try {
            ini_set('memory_limit', '1024M');
            $path = $this->excelFile->getRealPath();

            $reader = new Xlsx();
            $reader->setReadDataOnly(true);
            $worksheetInfo = $reader->listWorksheetInfo($path);
            $targetSheet = 'Sheet3 (3)';
            $found = false;
            foreach ($worksheetInfo as $info) {
                if ($info['worksheetName'] === $targetSheet) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                $targetSheet = $worksheetInfo[0]['worksheetName'];
            }

            $reader->setLoadSheetsOnly([$targetSheet]);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getSheetByName($targetSheet);
            $highestRow = $sheet->getHighestRow();

            $imported = 0;
            $updated = 0;

            DB::beginTransaction();

            for ($r = 6; $r <= $highestRow; $r++) {
                $mouldNo = trim((string) $sheet->getCell("J{$r}")->getValue());
                $partNo = trim((string) $sheet->getCell("E{$r}")->getValue());
                $partName = trim((string) $sheet->getCell("F{$r}")->getValue());
                $customer = trim((string) $sheet->getCell("I{$r}")->getValue());
                $mcNo = trim((string) $sheet->getCell("B{$r}")->getValue());
                $material = trim((string) $sheet->getCell("G{$r}")->getValue());

                if (empty($mouldNo) && empty($partNo)) {
                    continue;
                }

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

                $model = null;
                if (preg_match('/(?:5H45|4L45W|5L45W|26MY|[A-Z0-9]{3,6})/i', $customer . ' ' . $partName, $matches)) {
                    $model = trim($matches[0]);
                }

                $record = MasterMould::updateOrCreate(
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

                if ($record->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $updated++;
                }
            }

            DB::commit();

            $this->closeUploadModal();
            session()->flash('success', "Upload Excel berhasil! {$imported} mould baru ditambahkan, {$updated} diperbarui.");
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        } finally {
            $this->isUploading = false;
        }
    }

    public function render()
    {
        $query = MasterMould::query();

        if ($this->search) {
            $term = '%' . $this->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('mould_no', 'LIKE', $term)
                    ->orWhere('part_no', 'LIKE', $term)
                    ->orWhere('part_name', 'LIKE', $term)
                    ->orWhere('customer', 'LIKE', $term)
                    ->orWhere('machine_number', 'LIKE', $term);
            });
        }

        if ($this->selectedCustomer) {
            $query->where('customer', $this->selectedCustomer);
        }

        $moulds = $query->orderBy('mould_no')->paginate(20);

        $customerList = MasterMould::select('customer')
            ->distinct()
            ->orderBy('customer')
            ->pluck('customer')
            ->filter()
            ->values();

        $totalCount = MasterMould::count();
        $uniqueMouldCount = MasterMould::distinct('mould_no')->count('mould_no');

        return view('livewire.maintenance.master-mould.index', [
            'moulds' => $moulds,
            'customerList' => $customerList,
            'totalCount' => $totalCount,
            'uniqueMouldCount' => $uniqueMouldCount,
        ])->layout('layouts.app');
    }
}
