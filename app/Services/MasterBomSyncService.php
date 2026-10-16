<?php

namespace App\Services;

use App\Models\MasterBom;
use App\Models\MasterBomComponent;
use App\Models\MasterBomFgHeader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MasterBomSyncService
{
    /**
     * Jalankan proses Cleansing & Sinkronisasi dari staging master_boms ke 2 tabel produksi:
     * - master_bom_fg_headers (Master FG Sah)
     * - master_bom_components (Struktur Multilevel WIP & Raw Material)
     */
    public function syncAll(): array
    {
        // 1. Ambil seluruh data relasi mentah dari master_boms
        $allBoms = MasterBom::where('is_active', true)->get();

        if ($allBoms->isEmpty()) {
            return [
                'status' => 'empty',
                'message' => 'Tabel staging master_boms masih kosong. Silakan unggah file Excel SAP terlebih dahulu.',
                'fgs_count' => 0,
                'components_count' => 0,
                'anomalies' => [],
            ];
        }

        // Kelompokkan data berdasarkan parent_item
        $bomsByParent = $allBoms->groupBy('parent_item');
        $allParentCodes = $bomsByParent->keys()->flip();

        // 2. Deteksi Anomali SAP: Dummy Project Header / Grouping Folder (misal SHAD TOP CASE)
        $itemToProjectMap = [];
        $dummyParents = $this->detectDummyParents($bomsByParent, $itemToProjectMap);

        // 3. Identifikasi Finished Goods (FG) Sah
        $fgCandidates = $this->identifyFinishedGoods($bomsByParent, $dummyParents, $itemToProjectMap);

        DB::beginTransaction();
        try {
            // Bersihkan data lama di 2 tabel valid (Gunakan delete agar tidak memutus transaksi PDO)
            MasterBomComponent::query()->delete();
            MasterBomFgHeader::query()->delete();

            // Load existing PE audit verifications so they are preserved
            $verifications = \Illuminate\Support\Facades\Schema::hasTable('master_bom_verifications')
                ? \App\Models\MasterBomVerification::all()->keyBy('parent_item')
                : collect();

            $fgHeadersToInsert = [];
            $componentsToInsert = [];
            $now = now();
            $fgIdCounter = 1;

            $totalWips = 0;
            $totalRaws = 0;

            foreach ($fgCandidates as $fgCode => $fgMeta) {
                $fgId = $fgIdCounter++;
                $stats = [
                    'wip_count' => 0,
                    'raw_count' => 0,
                    'max_depth' => 1,
                    'has_packaging' => false,
                ];

                // Explode multilevel silsilah untuk FG ini
                $explodedComponents = [];
                $this->explodeFgComponents(
                    $fgId,
                    $fgCode,
                    $fgCode,
                    1.0,
                    1,
                    $fgCode,
                    $bomsByParent,
                    $allParentCodes,
                    $dummyParents,
                    $explodedComponents,
                    $stats,
                    [$fgCode => true] // branch visited set cegah circular
                );

                $verif = $verifications->get($fgCode);

                $fgHeadersToInsert[] = [
                    'id' => $fgId,
                    'fg_item_code' => $fgCode,
                    'fg_description' => $fgMeta['desc'] ?? null,
                    'project_code' => $fgMeta['project_code'] ?? null,
                    'family' => $fgMeta['family'] ?? null,
                    'customer_name' => $fgMeta['customer'] ?? null,
                    'uom' => $fgMeta['uom'] ?? 'PCS',
                    'total_wip_count' => $stats['wip_count'],
                    'total_raw_count' => $stats['raw_count'],
                    'max_depth_level' => $stats['max_depth'],
                    'has_packaging' => $stats['has_packaging'],
                    'is_verified' => $verif ? true : false,
                    'verified_at' => $verif?->verified_at,
                    'verified_by_name' => $verif?->verified_by_name,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $totalWips += $stats['wip_count'];
                $totalRaws += $stats['raw_count'];

                foreach ($explodedComponents as $comp) {
                    $comp['created_at'] = $now;
                    $comp['updated_at'] = $now;
                    $componentsToInsert[] = $comp;
                }
            }

            // Bulk insert Header
            foreach (array_chunk($fgHeadersToInsert, 500) as $chunk) {
                MasterBomFgHeader::insert($chunk);
            }

            // Bulk insert Components
            foreach (array_chunk($componentsToInsert, 1000) as $chunk) {
                MasterBomComponent::insert($chunk);
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Berhasil memvalidasi dan menyinkronkan data Master BOM ke 2 tabel produksi!',
                'fgs_count' => count($fgHeadersToInsert),
                'components_count' => count($componentsToInsert),
                'wips_count' => $totalWips,
                'raws_count' => $totalRaws,
                'anomalies' => array_keys($dummyParents),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error synchronizing BOMs: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    /**
     * Deteksi Parent yang merupakan Dummy Project / Family Folder di SAP (seperti 'SHAD TOP CASE')
     * Menggunakan config/bom_projects.php sebagai daftar 100% akurat, dan melengkapi dengan auto-update.
     */
    protected function detectDummyParents($bomsByParent, array &$itemToProjectMap = []): array
    {
        $dummyParents = [];

        // 1. Ambil list 100% akurat yang sudah terdaftar di config/bom_projects.php
        $configList = config('bom_projects.dummy_headers', []);
        $configMap = [];
        foreach ($configList as $item) {
            $configMap[strtoupper(trim((string) $item))] = true;
        }

        $detectedNew = false;

        foreach ($bomsByParent as $parentCode => $children) {
            $normalizedCode = strtoupper(trim((string) $parentCode));

            // Bahan baku dan hardware murni bukan dummy project
            if (str_starts_with($normalizedCode, '400-') || str_starts_with($normalizedCode, '401-') || str_starts_with($normalizedCode, '600-') || str_starts_with($normalizedCode, '300-')) {
                continue;
            }

            $firstDesc = $children->first()->parent_description ?? '';
            $normalizedDesc = strtoupper(trim((string) $firstDesc));

            $isDummy = false;
            $reason = '';

            // TINGKAT 1 (100% AKURASI): Cek langsung ke config/bom_projects.php
            if (isset($configMap[$normalizedCode])) {
                $isDummy = true;
                $reason = '100% Akurat (Terdaftar di config/bom_projects.php)';
            }
            // TINGKAT 2 (Deteksi Pola Project / Series / Parts for di SAP):
            elseif (
                str_contains($normalizedDesc, 'PLASTIC PART') ||
                str_contains($normalizedDesc, 'PLASTIC PARTS') ||
                str_contains($normalizedDesc, 'PARTS FOR') ||
                str_contains($normalizedDesc, 'PART FOR') ||
                str_contains($normalizedDesc, 'PARTS FROM') ||
                str_contains($normalizedDesc, 'PROJECT') ||
                str_contains($normalizedDesc, 'DUMMY') ||
                str_contains($normalizedDesc, 'FAMILY') ||
                str_contains($normalizedCode, 'SHAD TOP CASE') ||
                (str_contains($normalizedDesc, 'SERIES') && $children->count() >= 2)
            ) {
                $isDummy = true;
                $reason = 'Pola dummy project/series: ' . $firstDesc;
                $detectedNew = true;
            } elseif ($children->count() >= 2) {
                // Indikasi: Memiliki anak >= 2 dan > 50% anak-anaknya adalah Parent yang punya Box/Kemasan mandiri
                $childFgsCount = 0;
                foreach ($children as $c) {
                    if (isset($bomsByParent[$c->component_item])) {
                        $subChildren = $bomsByParent[$c->component_item];
                        if ($this->hasPackagingMaterial($subChildren)) {
                            $childFgsCount++;
                        }
                    }
                }

                if ($childFgsCount >= 2 && ($childFgsCount / $children->count()) >= 0.5) {
                    $isDummy = true;
                    $reason = "Berisi {$childFgsCount} produk FG mandiri ber-kemasan";
                    $detectedNew = true;
                }
            }

            if ($isDummy) {
                $dummyParents[$parentCode] = $reason;
                // Petakan setiap komponen anak ke project_code ini
                foreach ($children as $c) {
                    $cleanItem = trim((string) $c->component_item);
                    $itemToProjectMap[$cleanItem] = $parentCode;
                    $itemToProjectMap[rtrim($cleanItem, '.')] = $parentCode;
                    $itemToProjectMap[$cleanItem . '.'] = $parentCode;
                }
            }
        }

        // Jika terdeteksi project code baru yang belum ada di config, perbarui config/bom_projects.php
        $this->updateConfigFileIfChanged(array_keys($dummyParents));

        return $dummyParents;
    }

    /**
     * Tulis dan perbarui config/bom_projects.php secara otomatis
     */
    public function updateConfigFileIfChanged(array $allDummyParents): void
    {
        $existing = config('bom_projects.dummy_headers', []);
        $merged = array_values(array_unique(array_merge($existing, $allDummyParents)));
        sort($merged);

        // Jika ada perubahan, tulis ulang file config
        if ($merged !== $existing || ! file_exists(config_path('bom_projects.php'))) {
            $configPath = config_path('bom_projects.php');
            $exported = var_export($merged, true);

            $content = <<<PHP
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SAP BOM Dummy Project Headers (100% Accurate Exclusion List)
    |--------------------------------------------------------------------------
    |
    | Daftar parent item di SAP Business One yang sebenarnya adalah folder
    | nama project / family group (seperti 'SHAD TOP CASE', 'KULKAS SHARP', dll)
    | dan BUKAN merupakan Finished Goods mandiri.
    |
    | Setiap item di bawah ini akan:
    | 1. DI-SKIP dari tabel master_bom_fg_headers (tidak dibuatkan FG header).
    | 2. Ditugaskan sebagai 'project_code' pada setiap FG riil di bawahnya.
    |
    */

    'dummy_headers' => {$exported},

];

PHP;
            @file_put_contents($configPath, $content);
        }
    }

    /**
     * Identifikasi Finished Goods (FG) Sah
     */
    protected function identifyFinishedGoods($bomsByParent, array $dummyParents, array $itemToProjectMap = []): array
    {
        $fgs = [];

        // Buat map parent yang sah (bukan dummy)
        $realParents = $bomsByParent->reject(function ($children, $parent) use ($dummyParents) {
            return isset($dummyParents[$parent]);
        });

        // Kumpulkan seluruh item yang menjadi anak dari REAL PARENT
        $childOfRealParents = [];
        foreach ($realParents as $parentCode => $children) {
            foreach ($children as $c) {
                $childOfRealParents[$c->component_item] = true;
            }
        }

        foreach ($realParents as $parentCode => $children) {
            $first = $children->first();
            $desc = $first->parent_description ?? '';
            $uom = $first->uom ?? 'PCS';

            $hasPackaging = $this->hasPackagingMaterial($children);
            $isChildOfRealParent = isset($childOfRealParents[$parentCode]);

            // Item adalah FG jika:
            // 1. Punya packaging/kemasan sendiri (misal D0B29100.), ATAU
            // 2. Tidak pernah menjadi anak dari REAL PARENT mana pun (Top-level Father asli)
            if ($hasPackaging || ! $isChildOfRealParent) {
                // Ambil project code jika item ini berada di bawah folder project SAP
                $projectCode = $itemToProjectMap[$parentCode]
                    ?? ($itemToProjectMap[rtrim($parentCode, '.')] ?? null);

                // Ambil family: prioritas dari kolom family pada BOM parent ini
                $family = $children->pluck('family')->filter()->first();

                // Deteksi jika parentCode ada di list dummy/family
                if (empty($family)) {
                    $family = $projectCode;
                }

                // USER REQUIREMENT: Project mendeteksi Family juga!
                // Masukkan family ke grouping project jika project_code belum terisi
                if (empty($projectCode) && ! empty($family)) {
                    $projectCode = $family;
                }

                // Jika family belum terisi tapi projectCode ada, samakan ke family
                if (empty($family) && ! empty($projectCode)) {
                    $family = $projectCode;
                }

                // Deteksi customer dari project code atau nama/deskripsi
                $customer = $this->guessCustomer($projectCode ?: $parentCode, $desc);

                $fgs[$parentCode] = [
                    'desc' => $desc,
                    'uom' => $uom,
                    'project_code' => $projectCode,
                    'family' => $family,
                    'customer' => $customer,
                    'has_packaging' => $hasPackaging,
                ];
            }
        }

        return $fgs;
    }

    /**
     * Cek apakah daftar komponen mengandung kemasan / packaging (Box, Manual, Warranty, Bag)
     */
    protected function hasPackagingMaterial($children): bool
    {
        foreach ($children as $c) {
            $cat = MasterBom::determineCategory($c->component_item, $c->component_description ?? '', false);
            if ($cat['type'] === 'PACKAGING') {
                return true;
            }
        }

        return false;
    }

    /**
     * Rekursif explode multilevel komponen untuk FG tertentu
     */
    protected function explodeFgComponents(
        int $fgId,
        string $fgCode,
        string $currentParent,
        float $accumMultiplier,
        int $depthLevel,
        string $lineagePath,
        $bomsByParent,
        $allParentCodes,
        array $dummyParents,
        array &$explodedList,
        array &$stats,
        array $branchVisited
    ): void {
        if (! isset($bomsByParent[$currentParent])) {
            return;
        }

        $children = $bomsByParent[$currentParent];

        if ($depthLevel > $stats['max_depth']) {
            $stats['max_depth'] = $depthLevel;
        }

        foreach ($children as $c) {
            $compItem = $c->component_item;
            $normCompItem = strtoupper(trim((string) $compItem));

            // CRITICAL GUARD: Jangan pernah masukkan dummy project code ke dalam tabel komponen!
            if (isset($dummyParents[$compItem]) || isset($dummyParents[$normCompItem])) {
                continue;
            }

            $compDesc = $c->component_description ?? '';
            $unitQty = (float) $c->quantity;
            $totalQty = $accumMultiplier * $unitQty;
            $uom = $c->uom ?: 'PCS';

            // Apakah anak ini merupakan WIP (punya sub-komponen lagi dan bukan dummy parent)?
            $isWip = isset($allParentCodes[$compItem]) && ! isset($dummyParents[$compItem]);

            $cat = MasterBom::determineCategory($compItem, $compDesc, $isWip);
            $itemType = $cat['type'] ?? ($isWip ? 'WIP' : 'RAW_MATERIAL');

            if (($cat['type'] ?? '') === 'PACKAGING') {
                $stats['has_packaging'] = true;
            }

            if ($isWip) {
                $stats['wip_count']++;
            } else {
                $stats['raw_count']++;
            }

            $currentLineage = $lineagePath . ' > ' . $compItem;

            $explodedList[] = [
                'fg_id' => $fgId,
                'parent_item' => $currentParent,
                'component_item' => $compItem,
                'component_description' => $compDesc,
                'depth_level' => $depthLevel,
                'item_type' => $itemType,
                'is_wip' => $isWip,
                'unit_qty' => $unitQty,
                'total_qty_per_fg' => $totalQty,
                'uom' => $uom,
                'lineage_path' => $currentLineage,
            ];

            // Jika anak ini adalah WIP, telusuri lebih dalam (Lv 2, Lv 3, Lv 4, dst)
            if ($isWip && ! isset($branchVisited[$compItem])) {
                $newBranch = $branchVisited;
                $newBranch[$compItem] = true;

                $this->explodeFgComponents(
                    $fgId,
                    $fgCode,
                    $compItem,
                    $totalQty,
                    $depthLevel + 1,
                    $currentLineage,
                    $bomsByParent,
                    $allParentCodes,
                    $dummyParents,
                    $explodedList,
                    $stats,
                    $newBranch
                );
            }
        }
    }

    /**
     * Tebak customer dari kode atau deskripsi
     */
    protected function guessCustomer(string $itemCode, string $desc): ?string
    {
        $text = strtoupper($itemCode . ' ' . $desc);
        if (str_contains($text, 'SHAD') || str_starts_with($itemCode, 'D0B') || str_starts_with($itemCode, 'D1B') || str_starts_with($itemCode, 'K0') || str_starts_with($itemCode, 'H0') || str_starts_with($itemCode, 'Y0')) {
            return 'SHAD';
        }
        if (str_contains($text, 'YAMAHA') || str_contains($text, 'YMH')) {
            return 'YAMAHA';
        }
        if (str_contains($text, 'HONDA') || str_contains($text, 'AHM')) {
            return 'HONDA';
        }
        if (str_contains($text, 'KAWASAKI')) {
            return 'KAWASAKI';
        }

        return 'DAIJO';
    }
}
