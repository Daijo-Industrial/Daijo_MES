<?php

namespace App\Livewire\MaterialWarehouse;

use App\Imports\MasterListMaterialImport;
use App\Models\MasterListMaterial;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class MasterListMaterialIndex extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public int $perPage = 25;

    // File Upload State
    public $file;

    public bool $showUploadModal = false;

    public ?string $currentBatchId = null;

    public array $importProgress = [];

    // Manual Form Add/Edit State
    public bool $showItemModal = false;

    public ?int $editingId = null;

    public string $item_code = '';

    public string $item_description = '';

    public string $preferred_supplier = '';

    public string $purchasing_uom = '';

    protected $queryString = ['search'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public ?string $uploadError = null;

    public function openUploadModal(): void
    {
        $this->reset(['file', 'uploadError']);
        $this->showUploadModal = true;
    }

    public function startImport(): void
    {
        $this->uploadError = null;

        $this->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:51200', // max 50MB
        ]);

        try {
            $batchId = 'batch_' . time() . '_' . uniqid();
            $path = $this->file->store('imports', 'local');
            $fullPath = storage_path('app/' . $path);

            // Auto-detect actual file format (handles HTML/CSV saved with .xls extension)
            $readerType = null;
            try {
                $detected = \PhpOffice\PhpSpreadsheet\IOFactory::identify($fullPath);
                $map = [
                    'Xlsx' => \Maatwebsite\Excel\Excel::XLSX,
                    'Xls' => \Maatwebsite\Excel\Excel::XLS,
                    'Csv' => \Maatwebsite\Excel\Excel::CSV,
                    'Html' => \Maatwebsite\Excel\Excel::HTML,
                    'Xml' => \Maatwebsite\Excel\Excel::XML,
                ];
                $readerType = $map[$detected] ?? null;
            } catch (\Throwable $t) {
                // fallback to default auto-detection
            }

            Excel::queueImport(new MasterListMaterialImport($batchId), $path, 'local', $readerType);

            // Trigger background queue worker automatically so job doesn't stay stuck at 0
            try {
                if (class_exists('\Symfony\Component\Process\Process')) {
                    $process = new \Symfony\Component\Process\Process([PHP_BINARY, base_path('artisan'), 'queue:work', '--stop-when-empty', '--tries=1']);
                    $process->start();
                }
            } catch (\Throwable $t) {
                // Ignore if process creation is restricted
            }

            $this->currentBatchId = $batchId;
            $this->showUploadModal = false;
            $this->reset(['file', 'uploadError']);

            session()->flash('success', 'File Excel berhasil di-upload dan sedang diproses di background queue.');
        } catch (\Exception $e) {
            $this->uploadError = 'Gagal memproses file import: ' . $e->getMessage();
            session()->flash('error', 'Gagal memproses file import: ' . $e->getMessage());
        }
    }

    public function pollImportProgress(): void
    {
        if ($this->currentBatchId) {
            $cacheKey = 'mwh_import_progress_' . $this->currentBatchId;
            $data = Cache::get($cacheKey);
            if ($data) {
                $this->importProgress = $data;
                if (in_array($data['status'] ?? '', ['completed', 'failed'])) {
                    // Refresh data table after completion
                    $this->resetPage();
                }
            }
        }
    }

    public function clearImportBanner(): void
    {
        $this->currentBatchId = null;
        $this->importProgress = [];
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingId', 'item_code', 'item_description', 'preferred_supplier', 'purchasing_uom']);
        $this->showItemModal = true;
    }

    public function editItem(int $id): void
    {
        $item = MasterListMaterial::findOrFail($id);
        $this->editingId = $item->id;
        $this->item_code = $item->item_code;
        $this->item_description = $item->item_description ?? '';
        $this->preferred_supplier = $item->preferred_supplier ?? '';
        $this->purchasing_uom = $item->purchasing_uom ?? '';

        $this->showItemModal = true;
    }

    public function saveItem(): void
    {
        $this->validate([
            'item_code' => 'required|string|max:100|unique:master_list_materials,item_code,' . ($this->editingId ?? 'NULL') . ',id,deleted_at,NULL',
            'item_description' => 'nullable|string',
            'preferred_supplier' => 'nullable|string|max:100',
            'purchasing_uom' => 'nullable|string|max:50',
        ]);

        MasterListMaterial::updateOrCreate(
            ['id' => $this->editingId],
            [
                'item_code' => strtoupper(trim($this->item_code)),
                'item_description' => trim($this->item_description) ?: null,
                'preferred_supplier' => trim($this->preferred_supplier) ?: null,
                'purchasing_uom' => trim($this->purchasing_uom) ?: null,
            ]
        );

        session()->flash('success', 'Data Master Material berhasil disimpan.');
        $this->showItemModal = false;
        $this->reset(['editingId', 'item_code', 'item_description', 'preferred_supplier', 'purchasing_uom']);
    }

    public function deleteItem(int $id): void
    {
        $item = MasterListMaterial::find($id);
        if ($item) {
            $item->delete();
            session()->flash('success', 'Material ' . $item->item_code . ' berhasil dihapus.');
        }
    }

    public function render()
    {
        $materials = MasterListMaterial::query()
            ->when($this->search, function ($query) {
                $s = '%' . trim($this->search) . '%';
                $query->where('item_code', 'like', $s)
                    ->orWhere('item_description', 'like', $s)
                    ->orWhere('preferred_supplier', 'like', $s)
                    ->orWhere('purchasing_uom', 'like', $s);
            })
            ->orderBy('item_code', 'asc')
            ->paginate($this->perPage);

        $totalCount = MasterListMaterial::count();

        return view('livewire.material-warehouse.master-list-material-index', [
            'materials' => $materials,
            'totalCount' => $totalCount,
        ]);
    }
}
