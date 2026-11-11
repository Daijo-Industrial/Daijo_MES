<?php

namespace App\Livewire;

use App\Models\SecondProcessDefectAlias;
use App\Models\SecondProcessDefectCatalog;
use App\Models\SecondProcessNgRecord;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SecondProcessDefectManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $unmappedSearch = '';

    // Create Catalog form
    public string $newCatalogName = '';
    public string $newCatalogDescription = '';

    // Create Alias modal/form
    public ?int $selectedCatalogId = null;
    public string $newAliasInput = '';

    // Flash message
    public ?string $statusMessage = null;
    public string $statusType = 'success'; // success, error, info

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        \Illuminate\Support\Facades\Gate::authorize('second-process-reports');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function createCatalog(): void
    {
        $this->validate([
            'newCatalogName' => 'required|string|min:2|max:100',
            'newCatalogDescription' => 'nullable|string|max:255',
        ]);

        $name = strtoupper(trim($this->newCatalogName));

        if (SecondProcessDefectCatalog::where('name', $name)->exists()) {
            $this->addError('newCatalogName', "Defect '{$name}' sudah ada di katalog.");
            return;
        }

        // Also ensure it's not already registered as an alias
        if (SecondProcessDefectAlias::where('alias', $name)->exists()) {
            $this->addError('newCatalogName', "Nama '{$name}' sudah terdaftar sebagai alias.");
            return;
        }

        SecondProcessDefectCatalog::create([
            'name' => $name,
            'description' => $this->newCatalogDescription ? trim($this->newCatalogDescription) : null,
            'is_active' => true,
        ]);

        $this->newCatalogName = '';
        $this->newCatalogDescription = '';
        $this->notify("Defect standar '{$name}' berhasil ditambahkan ke katalog.", 'success');
    }

    public function toggleCatalogActive(int $catalogId): void
    {
        $catalog = SecondProcessDefectCatalog::find($catalogId);
        if ($catalog) {
            $catalog->is_active = ! $catalog->is_active;
            $catalog->save();
            $this->notify("Status defect '{$catalog->name}' diubah menjadi " . ($catalog->is_active ? 'AKTIF' : 'NONAKTIF'), 'info');
        }
    }

    public function deleteCatalog(int $catalogId): void
    {
        $catalog = SecondProcessDefectCatalog::find($catalogId);
        if ($catalog) {
            $name = $catalog->name;
            $catalog->delete();
            $this->notify("Defect standar '{$name}' dan seluruh aliasnya berhasil dihapus.", 'info');
        }
    }

    public function openAddAliasModal(int $catalogId): void
    {
        $this->selectedCatalogId = $catalogId;
        $this->newAliasInput = '';
    }

    public function closeAddAliasModal(): void
    {
        $this->selectedCatalogId = null;
        $this->newAliasInput = '';
    }

    public function addAlias(): void
    {
        $this->validate([
            'selectedCatalogId' => 'required|exists:second_process_defect_catalogs,id',
            'newAliasInput' => 'required|string|min:2|max:100',
        ]);

        $catalog = SecondProcessDefectCatalog::findOrFail($this->selectedCatalogId);
        $alias = strtoupper(trim($this->newAliasInput));

        if ($alias === $catalog->name) {
            $this->addError('newAliasInput', "Alias tidak boleh sama persis dengan nama standar '{$catalog->name}'.");
            return;
        }

        if (SecondProcessDefectCatalog::where('name', $alias)->exists()) {
            $this->addError('newAliasInput', "Nama '{$alias}' sudah digunakan sebagai nama defect standar lain.");
            return;
        }

        if (SecondProcessDefectAlias::where('alias', $alias)->exists()) {
            $this->addError('newAliasInput', "Alias '{$alias}' sudah terdaftar.");
            return;
        }

        SecondProcessDefectAlias::create([
            'defect_catalog_id' => $catalog->id,
            'alias' => $alias,
        ]);

        $this->newAliasInput = '';
        $this->closeAddAliasModal();
        $this->notify("Alias '{$alias}' berhasil ditautkan ke '{$catalog->name}'.", 'success');
    }

    public function removeAlias(int $aliasId): void
    {
        $alias = SecondProcessDefectAlias::find($aliasId);
        if ($alias) {
            $aliasName = $alias->alias;
            $alias->delete();
            $this->notify("Alias '{$aliasName}' berhasil dihapus.", 'info');
        }
    }

    public function assignUnmappedToCatalog(string $rawName, int $catalogId): void
    {
        $clean = strtoupper(trim($rawName));
        $catalog = SecondProcessDefectCatalog::findOrFail($catalogId);

        DB::beginTransaction();
        try {
            if ($clean !== $catalog->name && ! SecondProcessDefectAlias::where('alias', $clean)->exists()) {
                SecondProcessDefectAlias::create([
                    'defect_catalog_id' => $catalog->id,
                    'alias' => $clean,
                ]);
            }

            // Also standardize records immediately
            $updated = SecondProcessNgRecord::where('ng_name', $rawName)
                ->update(['ng_name' => $catalog->name]);

            DB::commit();
            $this->notify("Variasi '{$rawName}' berhasil dipetakan ke '{$catalog->name}' ({$updated} record diperbarui).", 'success');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->notify("Gagal memetakan variasi: " . $e->getMessage(), 'error');
        }
    }

    public function promoteUnmappedToCatalog(string $rawName): void
    {
        $clean = strtoupper(trim($rawName));

        DB::beginTransaction();
        try {
            $catalog = SecondProcessDefectCatalog::firstOrCreate(
                ['name' => $clean],
                ['is_active' => true, 'description' => 'Dipromosikan dari variasi produksi']
            );

            // Clean up exact whitespace differences in records
            $updated = SecondProcessNgRecord::where('ng_name', $rawName)
                ->update(['ng_name' => $catalog->name]);

            DB::commit();
            $this->notify("'{$clean}' berhasil dipromosikan menjadi defect standar baru ({$updated} record diperbarui).", 'success');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->notify("Gagal mempromosikan variasi: " . $e->getMessage(), 'error');
        }
    }

    public function standardizeBacklog(): void
    {
        $aliases = SecondProcessDefectAlias::with('catalog')->get();
        $aliasMap = [];
        foreach ($aliases as $item) {
            if ($item->catalog) {
                $aliasMap[strtoupper(trim($item->alias))] = $item->catalog->name;
            }
        }

        $canonicalNames = SecondProcessDefectCatalog::pluck('name')->map(fn ($n) => strtoupper(trim($n)))->toArray();
        $canonicalLookup = array_flip($canonicalNames);

        $distinctNames = SecondProcessNgRecord::selectRaw('ng_name, COUNT(*) as cnt')
            ->groupBy('ng_name')
            ->pluck('cnt', 'ng_name')
            ->toArray();

        $replacements = [];
        $totalRecords = 0;

        foreach ($distinctNames as $rawName => $count) {
            $upper = strtoupper(trim((string) $rawName));
            $target = null;

            if (isset($aliasMap[$upper])) {
                $target = $aliasMap[$upper];
            } elseif (isset($canonicalLookup[$upper]) && $rawName !== $upper) {
                $target = $upper;
            }

            if ($target !== null && $rawName !== $target) {
                $replacements[$rawName] = $target;
                $totalRecords += $count;
            }
        }

        if (empty($replacements)) {
            $this->notify("Semua record NG saat ini sudah standar sesuai katalog. Tidak ada perubahan yang diperlukan.", 'info');
            return;
        }

        DB::beginTransaction();
        try {
            foreach ($replacements as $rawName => $target) {
                SecondProcessNgRecord::where('ng_name', $rawName)->update(['ng_name' => $target]);
            }
            DB::commit();
            $this->notify("Berhasil menstandarisasi {$totalRecords} record NG di seluruh database!", 'success');
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->notify("Gagal menjalankan standarisasi: " . $e->getMessage(), 'error');
        }
    }

    protected function notify(string $message, string $type = 'success'): void
    {
        $this->statusMessage = $message;
        $this->statusType = $type;
    }

    public function render()
    {
        // 1. Fetch unmapped defect variations from database
        $registeredCanonicals = SecondProcessDefectCatalog::pluck('name')->map(fn ($n) => strtoupper(trim($n)))->toArray();
        $registeredAliases = SecondProcessDefectAlias::pluck('alias')->map(fn ($a) => strtoupper(trim($a)))->toArray();
        $knownPool = array_flip(array_merge($registeredCanonicals, $registeredAliases));

        $allRawNames = SecondProcessNgRecord::selectRaw('ng_name, COUNT(*) as cnt')
            ->groupBy('ng_name')
            ->orderByDesc('cnt')
            ->get();

        $unmappedList = [];
        foreach ($allRawNames as $item) {
            $upper = strtoupper(trim((string) $item->ng_name));
            if (! isset($knownPool[$upper]) && $upper !== '') {
                if (empty($this->unmappedSearch) || str_contains($upper, strtoupper(trim($this->unmappedSearch)))) {
                    $unmappedList[] = [
                        'raw_name' => $item->ng_name,
                        'count' => $item->cnt,
                    ];
                }
            }
        }

        // 2. Fetch catalogs with alias relations and record counts
        $catalogsQuery = SecondProcessDefectCatalog::with(['aliases' => function ($q) {
            $q->orderBy('alias');
        }])
        ->when($this->search, function ($q) {
            $term = '%' . trim($this->search) . '%';
            $q->where('name', 'like', $term)
              ->orWhere('description', 'like', $term)
              ->orWhereHas('aliases', function ($sub) use ($term) {
                  $sub->where('alias', 'like', $term);
              });
        })
        ->orderBy('name');

        $catalogs = $catalogsQuery->paginate(15);

        // Preload counts for all canonicals in current page
        $catalogNames = $catalogs->pluck('name')->toArray();
        $recordCounts = SecondProcessNgRecord::whereIn('ng_name', $catalogNames)
            ->selectRaw('ng_name, COUNT(*) as cnt')
            ->groupBy('ng_name')
            ->pluck('cnt', 'ng_name')
            ->toArray();

        // All active catalogs for the assign dropdown
        $allCatalogs = SecondProcessDefectCatalog::where('is_active', true)->orderBy('name')->get();

        return view('livewire.second-process-defect-manager', [
            'catalogs' => $catalogs,
            'unmappedList' => $unmappedList,
            'recordCounts' => $recordCounts,
            'allCatalogs' => $allCatalogs,
            'totalUnmappedCount' => count($unmappedList),
        ])->layout('layouts.app', ['title' => 'Master Defect Catalog & Alias Management - Second Process']);
    }
}
