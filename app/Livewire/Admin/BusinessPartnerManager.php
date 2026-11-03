<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
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
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $groupFilter = 'ALL';

    public string $industryFilter = 'ALL';

    public string $branchFilter = 'ALL';

    // File Upload
    public $file;

    public bool $showUploadModal = false;

    public bool $isProcessing = false;

    public bool $autoBranchKarawang = false;

    public bool $defaultBranchJakarta = false;

    public string $uploadMessage = '';

    public string $uploadError = '';

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'groupFilter' => ['except' => 'ALL'],
        'industryFilter' => ['except' => 'ALL'],
        'branchFilter' => ['except' => 'ALL'],
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

    public function setIndustryFilter(string $ind): void
    {
        $this->industryFilter = $ind;
        $this->resetPage();
    }

    public function setBranchFilter(string $branch): void
    {
        $this->branchFilter = $branch;
        $this->resetPage();
    }

    public function updateIndustry(int $id, string $industry): void
    {
        $allowed = [
            MasterBusinessPartner::INDUSTRY_AUTOMOTIVE,
            MasterBusinessPartner::INDUSTRY_ELECTRONICS,
            MasterBusinessPartner::INDUSTRY_MOULDING,
            MasterBusinessPartner::INDUSTRY_GENERAL,
        ];
        $norm = strtoupper(trim($industry));
        if (! in_array($norm, $allowed, true)) {
            return;
        }

        $bp = MasterBusinessPartner::findOrFail($id);
        $bp->update(['industry' => $norm]);
    }

    public function updateBranch(int $id, $branchId): void
    {
        $bp = MasterBusinessPartner::findOrFail($id);
        $val = ! empty($branchId) ? (int) $branchId : null;
        if ($val !== null) {
            $exists = Branch::where('id', $val)->where('is_active', true)->exists();
            if (! $exists) {
                return;
            }
        }
        $bp->update(['branch_id' => $val]);
    }

    public function openUploadModal(): void
    {
        $this->reset(['file', 'uploadMessage', 'uploadError', 'autoBranchKarawang', 'defaultBranchJakarta']);
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->reset(['file', 'uploadMessage', 'uploadError', 'autoBranchKarawang', 'defaultBranchJakarta']);
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
                'sales_employee' => null,
                'industry' => null,
                'branch' => null,
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
                } elseif (str_contains($norm, 'sales') || str_contains($norm, 'employee') || str_contains($norm, 'salesman')) {
                    $colIndex['sales_employee'] = $colLetter;
                } elseif (str_contains($norm, 'industry') || str_contains($norm, 'industri') || str_contains($norm, 'sektor')) {
                    $colIndex['industry'] = $colLetter;
                } elseif (str_contains($norm, 'branch') || str_contains($norm, 'cabang') || str_contains($norm, 'plant')) {
                    $colIndex['branch'] = $colLetter;
                }
            }

            // Defaults matching standard SAP Business Partners layout
            $colIndex['bp_code'] = $colIndex['bp_code'] ?? 'B';
            $colIndex['bp_name'] = $colIndex['bp_name'] ?? 'C';
            $colIndex['foreign_name'] = $colIndex['foreign_name'] ?? 'D';
            $colIndex['type'] = $colIndex['type'] ?? 'E';
            $colIndex['group_code'] = $colIndex['group_code'] ?? 'F';
            $colIndex['sales_employee'] = $colIndex['sales_employee'] ?? 'G';

            $isSuperAdmin = auth()->user()?->hasRole('SUPER-ADMIN') ?? false;
            $applyAutoBranchKarawang = $this->autoBranchKarawang && $isSuperAdmin;
            $applyDefaultBranchJakarta = $this->defaultBranchJakarta && $isSuperAdmin;

            $branchMap = Branch::active()->pluck('id', 'code')->all();
            $branchNameMap = Branch::active()->pluck('id', 'name')->all();
            $karawangBranchId = $branchMap['KRW'] ?? Branch::where('code', 'KRW')->orWhere('name', 'LIKE', '%Karawang%')->value('id');
            $jakartaBranchId = $branchMap['JKT'] ?? Branch::where('code', 'JKT')->orWhere('name', 'LIKE', '%Jakarta%')->value('id');

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

                $rawSales = isset($colIndex['sales_employee']) && isset($row[$colIndex['sales_employee']])
                    ? trim((string) $row[$colIndex['sales_employee']])
                    : '';
                $salesEmployee = null;
                if ($rawSales !== '' && ! str_contains(strtolower($rawSales), 'no sales employee')) {
                    $salesEmployee = $rawSales;
                }

                $rawIndustry = isset($colIndex['industry']) && isset($row[$colIndex['industry']])
                    ? strtoupper(trim((string) $row[$colIndex['industry']]))
                    : '';
                if (in_array($rawIndustry, [
                    MasterBusinessPartner::INDUSTRY_AUTOMOTIVE,
                    MasterBusinessPartner::INDUSTRY_ELECTRONICS,
                    MasterBusinessPartner::INDUSTRY_MOULDING,
                    MasterBusinessPartner::INDUSTRY_GENERAL,
                ], true)) {
                    $industry = $rawIndustry;
                } else {
                    $industry = MasterBusinessPartner::detectIndustry($bpName, $foreignName ?: '', $salesEmployee, $type);
                }

                $rawBranch = isset($colIndex['branch']) && isset($row[$colIndex['branch']])
                    ? strtoupper(trim((string) $row[$colIndex['branch']]))
                    : '';
                $branchId = null;
                if ($rawBranch !== '') {
                    if (isset($branchMap[$rawBranch])) {
                        $branchId = $branchMap[$rawBranch];
                    } else {
                        foreach ($branchNameMap as $bName => $bId) {
                            if (str_contains(strtoupper($bName), $rawBranch)) {
                                $branchId = $bId;
                                break;
                            }
                        }
                    }
                }

                // If Super-Admin toggled auto-branch Karawang, all business partners starting with 'K' are branched to Karawang
                if ($applyAutoBranchKarawang && str_starts_with(strtoupper($bpCode), 'K')) {
                    $branchId = $karawangBranchId;
                } elseif ($applyDefaultBranchJakarta && $branchId === null) {
                    // If Super-Admin toggled default Jakarta branch, default unresolved branch to Jakarta
                    $branchId = $jakartaBranchId;
                }

                $bpBatch[] = [
                    'bp_code' => $bpCode,
                    'bp_name' => $bpName,
                    'group_code' => $groupCode ?: null,
                    'category' => $category,
                    'industry' => $industry,
                    'branch_id' => $branchId,
                    'type' => $type ?: null,
                    'foreign_name' => $foreignName ?: null,
                    'sales_employee' => $salesEmployee,
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

            DB::transaction(function () use (
                $bpBatch,
                $customerBatch,
                $applyAutoBranchKarawang,
                $karawangBranchId,
                $applyDefaultBranchJakarta,
                $jakartaBranchId
            ) {
                // Upsert to master_business_partners in chunks.
                // NOTE: 'industry' and 'branch_id' are intentionally excluded from the update columns
                // so that MES-exclusive tags manually configured or detected on existing records remain persisted!
                foreach (array_chunk($bpBatch, 500) as $chunk) {
                    MasterBusinessPartner::upsert(
                        $chunk,
                        ['bp_code'],
                        ['bp_name', 'group_code', 'category', 'type', 'foreign_name', 'sales_employee', 'updated_at']
                    );
                }

                // If auto-branch Karawang is toggled, ensure all records starting with 'K' in this batch are branched to Karawang
                if ($applyAutoBranchKarawang && $karawangBranchId) {
                    $kCodes = collect($bpBatch)
                        ->filter(fn ($item) => str_starts_with(strtoupper($item['bp_code']), 'K'))
                        ->pluck('bp_code')
                        ->all();

                    if (! empty($kCodes)) {
                        foreach (array_chunk($kCodes, 500) as $chunkCodes) {
                            MasterBusinessPartner::whereIn('bp_code', $chunkCodes)
                                ->update(['branch_id' => $karawangBranchId]);
                        }
                    }
                }

                // If default assign to Jakarta branch is toggled, ensure remaining records in this batch are branched to Jakarta
                if ($applyDefaultBranchJakarta && $jakartaBranchId) {
                    $jktCodes = collect($bpBatch)
                        ->filter(function ($item) use ($applyAutoBranchKarawang) {
                            if ($applyAutoBranchKarawang && str_starts_with(strtoupper($item['bp_code']), 'K')) {
                                return false;
                            }
                            return true;
                        })
                        ->pluck('bp_code')
                        ->all();

                    if (! empty($jktCodes)) {
                        foreach (array_chunk($jktCodes, 500) as $chunkCodes) {
                            MasterBusinessPartner::whereIn('bp_code', $chunkCodes)
                                ->update(['branch_id' => $jakartaBranchId]);
                        }
                    }
                }

                // Synchronize CUSTOMER records to master_customer_delivery
                if (! empty($customerBatch)) {
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
            $extraInfo = [];
            if ($applyAutoBranchKarawang) {
                $kCount = collect($bpBatch)->filter(fn ($item) => str_starts_with(strtoupper($item['bp_code']), 'K'))->count();
                $extraInfo[] = "Auto-branch Karawang aktif ({$kCount} partner berawalan 'K' dipetakan ke Karawang)";
            }
            if ($applyDefaultBranchJakarta) {
                $jktCount = collect($bpBatch)->filter(function ($item) use ($applyAutoBranchKarawang) {
                    return ! ($applyAutoBranchKarawang && str_starts_with(strtoupper($item['bp_code']), 'K'));
                })->count();
                $extraInfo[] = "Default Jakarta aktif ({$jktCount} partner dipetakan ke Jakarta)";
            }
            $infoText = ! empty($extraInfo) ? ' ' . implode(', ', $extraInfo) . '.' : '';
            $this->uploadMessage = "Berhasil mengimpor & meng-update {$totalProcessed} Business Partners (Customer: {$customerCount}, Vendor: {$vendorCount}).{$infoText} Tag eksklusif MES (Sektor Industri & Plant) pada data existing tetap dipertahankan.";
            $this->reset(['file', 'autoBranchKarawang', 'defaultBranchJakarta']);
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
        $query = MasterBusinessPartner::with('branch');

        if ($this->search !== '') {
            $s = trim($this->search);
            $query->where(function ($q) use ($s) {
                $q->where('bp_code', 'LIKE', "%{$s}%")
                    ->orWhere('bp_name', 'LIKE', "%{$s}%")
                    ->orWhere('foreign_name', 'LIKE', "%{$s}%")
                    ->orWhere('type', 'LIKE', "%{$s}%")
                    ->orWhere('group_code', 'LIKE', "%{$s}%")
                    ->orWhere('sales_employee', 'LIKE', "%{$s}%")
                    ->orWhere('industry', 'LIKE', "%{$s}%")
                    ->orWhereHas('branch', function ($bq) use ($s) {
                        $bq->where('name', 'LIKE', "%{$s}%")
                            ->orWhere('code', 'LIKE', "%{$s}%");
                    });
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

        if ($this->industryFilter !== 'ALL') {
            $query->where('industry', $this->industryFilter);
        }

        if ($this->branchFilter !== 'ALL') {
            if ($this->branchFilter === 'UNASSIGNED') {
                $query->whereNull('branch_id');
            } else {
                $query->where('branch_id', (int) $this->branchFilter);
            }
        }

        $businessPartners = $query->orderBy('bp_code')->paginate(20);

        $branches = Branch::active()->orderBy('name')->get();

        $branchCounts = [
            'ALL' => MasterBusinessPartner::count(),
            'UNASSIGNED' => MasterBusinessPartner::whereNull('branch_id')->count(),
        ];
        foreach ($branches as $b) {
            $branchCounts[$b->id] = MasterBusinessPartner::where('branch_id', $b->id)->count();
        }

        // Counts for tabs
        $counts = [
            'ALL' => MasterBusinessPartner::count(),
            '100' => MasterBusinessPartner::where('group_code', '100')->count(),
            '101' => MasterBusinessPartner::where('group_code', '101')->count(),
            '102' => MasterBusinessPartner::where('group_code', '102')->count(),
            'AUTOMOTIVE' => MasterBusinessPartner::where('industry', MasterBusinessPartner::INDUSTRY_AUTOMOTIVE)->count(),
            'ELECTRONICS' => MasterBusinessPartner::where('industry', MasterBusinessPartner::INDUSTRY_ELECTRONICS)->count(),
            'MOULDING' => MasterBusinessPartner::where('industry', MasterBusinessPartner::INDUSTRY_MOULDING)->count(),
            'GENERAL' => MasterBusinessPartner::where('industry', MasterBusinessPartner::INDUSTRY_GENERAL)->count(),
        ];

        return view('livewire.admin.business-partner-manager', [
            'businessPartners' => $businessPartners,
            'counts' => $counts,
            'branches' => $branches,
            'branchCounts' => $branchCounts,
        ]);
    }
}
