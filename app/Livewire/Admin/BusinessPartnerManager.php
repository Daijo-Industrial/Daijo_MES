<?php

namespace App\Livewire\Admin;

use App\Models\MasterBusinessPartner;
use App\Models\MasterCustomerDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BusinessPartnerManager extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $groupFilter = 'ALL';

    // File Upload
    public $file;
    public bool $showUploadModal = false;
    public bool $isProcessing = false;
    public string $uploadMessage = '';
    public string $uploadError = '';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'groupFilter' => ['except' => 'ALL'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setGroupFilter(string $group): void
    {
        $this->groupFilter = $group;
        $this->resetPage();
    }

    public function openUploadModal(): void
    {
        $this->reset(['file', 'uploadMessage', 'uploadError']);
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->reset(['file', 'uploadMessage', 'uploadError']);
    }

    public function uploadFile(): void
    {
        $this->validate([
            'file' => 'required|file|max:20480', // 20MB limit
        ], [
            'file.required' => 'Pilih file Excel / XLS terlebih dahulu.',
            'file.max' => 'Ukuran file maksimal 20MB.',
        ]);

        $this->isProcessing = true;
        $this->uploadError = '';
        $this->uploadMessage = '';

        try {
            $realPath = $this->file->getRealPath();

            $spreadsheet = IOFactory::load($realPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (empty($rows) || count($rows) < 2) {
                $this->uploadError = 'File kosong atau tidak memiliki baris data.';
                $this->isProcessing = false;
                return;
            }

            $headerRow = array_shift($rows);

            // Dynamically detect column indexes
            $colIndex = [
                'bp_code' => null,
                'bp_name' => null,
                'group_code' => null,
                'type' => null,
                'foreign_name' => null,
            ];

            foreach ($headerRow as $colLetter => $headerName) {
                $norm = strtolower(trim((string) $headerName));
                if (str_contains($norm, 'bp code') || str_contains($norm, 'cardcode') || str_contains($norm, 'kode bp')) {
                    $colIndex['bp_code'] = $colLetter;
                } elseif (str_contains($norm, 'bp name') || str_contains($norm, 'cardname') || str_contains($norm, 'nama bp')) {
                    $colIndex['bp_name'] = $colLetter;
                } elseif (str_contains($norm, 'group') || str_contains($norm, 'grup')) {
                    $colIndex['group_code'] = $colLetter;
                } elseif (str_contains($norm, 'type') || str_contains($norm, 'tipe')) {
                    $colIndex['type'] = $colLetter;
                } elseif (str_contains($norm, 'foreign') || str_contains($norm, 'alias')) {
                    $colIndex['foreign_name'] = $colLetter;
                }
            }

            // Defaults matching standard SAP Business Partners layout
            $colIndex['bp_code'] = $colIndex['bp_code'] ?? 'B';
            $colIndex['bp_name'] = $colIndex['bp_name'] ?? 'C';
            $colIndex['group_code'] = $colIndex['group_code'] ?? 'D';
            $colIndex['type'] = $colIndex['type'] ?? 'E';

            $bpBatch = [];
            $customerBatch = [];
            $now = now();
            $customerCount = 0;
            $vendorCount = 0;

            foreach ($rows as $row) {
                $bpCode = isset($row[$colIndex['bp_code']]) ? trim((string) $row[$colIndex['bp_code']]) : '';
                $bpName = isset($row[$colIndex['bp_name']]) ? trim((string) $row[$colIndex['bp_name']]) : '';

                if ($bpCode === '' || $bpName === '') {
                    continue;
                }

                $groupCode = isset($row[$colIndex['group_code']]) ? trim((string) $row[$colIndex['group_code']]) : '';
                $category = MasterBusinessPartner::classifyCategory($groupCode, $bpCode);

                if ($category === MasterBusinessPartner::CATEGORY_CUSTOMER) {
                    $customerCount++;
                } else {
                    $vendorCount++;
                }

                $type = isset($row[$colIndex['type']]) ? trim((string) $row[$colIndex['type']]) : null;
                $foreignName = isset($colIndex['foreign_name']) && isset($row[$colIndex['foreign_name']])
                    ? trim((string) $row[$colIndex['foreign_name']])
                    : null;

                $bpBatch[] = [
                    'bp_code' => $bpCode,
                    'bp_name' => $bpName,
                    'group_code' => $groupCode ?: null,
                    'category' => $category,
                    'type' => $type ?: null,
                    'foreign_name' => $foreignName ?: null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($category === MasterBusinessPartner::CATEGORY_CUSTOMER) {
                    $customerBatch[] = [
                        'customer_code' => $bpCode,
                        'customer_name' => $bpName,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (empty($bpBatch)) {
                $this->uploadError = 'Tidak ada baris data valid yang ditemukan dalam file.';
                $this->isProcessing = false;
                return;
            }

            DB::transaction(function () use ($bpBatch, $customerBatch) {
                // Upsert to master_business_partners in chunks
                foreach (array_chunk($bpBatch, 500) as $chunk) {
                    MasterBusinessPartner::upsert(
                        $chunk,
                        ['bp_code'],
                        ['bp_name', 'group_code', 'category', 'type', 'foreign_name', 'updated_at']
                    );
                }

                // Synchronize CUSTOMER records to master_customer_delivery
                if (!empty($customerBatch)) {
                    foreach (array_chunk($customerBatch, 500) as $chunk) {
                        MasterCustomerDelivery::upsert(
                            $chunk,
                            ['customer_code'],
                            ['customer_name', 'updated_at']
                        );
                    }
                }
            });

            $totalProcessed = count($bpBatch);
            $this->uploadMessage = "Berhasil mengimpor & meng-update {$totalProcessed} Business Partners (Customer: {$customerCount}, Vendor: {$vendorCount}). {$customerCount} Customer telah disinkronkan ke Master Customer Delivery.";
            $this->reset('file');
            $this->resetPage();

        } catch (\Throwable $e) {
            Log::error('BusinessPartner upload error', ['exception' => $e]);
            $this->uploadError = 'Gagal memproses file: ' . $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        $query = MasterBusinessPartner::query();

        if ($this->search !== '') {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('bp_code', 'LIKE', "%{$s}%")
                    ->orWhere('bp_name', 'LIKE', "%{$s}%")
                    ->orWhere('foreign_name', 'LIKE', "%{$s}%")
                    ->orWhere('type', 'LIKE', "%{$s}%")
                    ->orWhere('group_code', 'LIKE', "%{$s}%");
            });
        }

        if ($this->groupFilter !== 'ALL') {
            if ($this->groupFilter === 'CUSTOMER') {
                $query->where('category', MasterBusinessPartner::CATEGORY_CUSTOMER);
            } elseif ($this->groupFilter === 'VENDOR') {
                $query->where('category', MasterBusinessPartner::CATEGORY_VENDOR);
            } else {
                $query->where('group_code', $this->groupFilter);
            }
        }

        $businessPartners = $query->orderBy('bp_code')->paginate(20);

        // Counts for tabs
        $counts = [
            'ALL' => MasterBusinessPartner::count(),
            '100' => MasterBusinessPartner::where('group_code', '100')->count(),
            '101' => MasterBusinessPartner::where('group_code', '101')->count(),
            '102' => MasterBusinessPartner::where('group_code', '102')->count(),
        ];

        return view('livewire.admin.business-partner-manager', [
            'businessPartners' => $businessPartners,
            'counts' => $counts,
        ]);
    }
}
