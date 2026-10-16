<?php

namespace App\Services;

use App\Models\MasterBom;
use App\Models\MasterBomComponent;
use App\Models\MasterBomFgHeader;
use Illuminate\Support\Facades\DB;

class SpkBomService
{
    /**
     * Mengambil daftar komponen Leaf (Non-WIP) dari Master BOM untuk suatu item SPK
     * Menghitung planned material quantity = unit_qty * planned_quantity
     *
     * @param string $itemCode Kode item Finished Good atau Sub-assembly dari SPK
     * @param float $plannedQty Quantity rencana produksi pada SPK
     */
    public function getLeafMaterials(string $itemCode, float $plannedQty = 1.0): array
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return [
                'source' => 'none',
                'item_code' => '',
                'planned_qty' => $plannedQty,
                'materials' => [],
                'total_materials_count' => 0,
            ];
        }

        // 1. Prioritas 1: Ambil dari Tabel Produksi Resmi (master_bom_fg_headers & master_bom_components)
        $fgHeader = MasterBomFgHeader::where('fg_item_code', $itemCode)->first();

        if ($fgHeader) {
            $components = MasterBomComponent::where('fg_id', $fgHeader->id)
                ->where('is_wip', false) // HANYA NON-WIP!
                ->select(
                    'component_item',
                    DB::raw('MAX(component_description) as component_description'),
                    DB::raw('MAX(item_type) as item_type'),
                    DB::raw('MAX(uom) as uom'),
                    DB::raw('SUM(total_qty_per_fg) as unit_qty')
                )
                ->groupBy('component_item')
                ->orderBy('component_item')
                ->get();

            if ($components->isNotEmpty()) {
                $materials = $components->map(function ($comp) use ($plannedQty) {
                    $unitQty = (float) $comp->unit_qty;
                    $plannedMaterialQty = $unitQty * $plannedQty;
                    $cat = MasterBom::formatCategoryFromType($comp->item_type ?? 'RAW_MATERIAL');

                    return [
                        'component_item' => $comp->component_item,
                        'component_description' => $comp->component_description ?: '-',
                        'item_type' => $comp->item_type,
                        'category_label' => $cat['label'],
                        'category_icon' => $cat['icon'],
                        'category_badge' => $cat['badge'],
                        'uom' => $comp->uom ?: 'PCS',
                        'unit_qty' => $unitQty,
                        'planned_material_qty' => $plannedMaterialQty,
                    ];
                })->values()->toArray();

                return [
                    'source' => 'production',
                    'item_code' => $itemCode,
                    'fg_description' => $fgHeader->fg_description,
                    'project_code' => $fgHeader->project_code ?: $fgHeader->family,
                    'planned_qty' => $plannedQty,
                    'materials' => $materials,
                    'total_materials_count' => count($materials),
                ];
            }
        }

        // 2. Prioritas 2: Fallback ke Master BOM Staging (master_boms) jika item belum divalidasi ke FG headers
        // (Misalnya part WIP / sub-assembly yang dibuatkan SPK terpisah di pabrik)
        $stagingSummary = MasterBom::getFlattenedSummary($itemCode, 1.0);

        if (! empty($stagingSummary['materials'])) {
            $materials = collect($stagingSummary['materials'])->map(function ($mat) use ($plannedQty) {
                $unitQty = (float) $mat['total_qty'];
                $plannedMaterialQty = $unitQty * $plannedQty;
                $cat = $mat['category'] ?? MasterBom::determineCategory($mat['item_code'], $mat['description'] ?? '', false);

                return [
                    'component_item' => $mat['item_code'],
                    'component_description' => $mat['description'] ?: '-',
                    'item_type' => $cat['type'],
                    'category_label' => $cat['label'],
                    'category_icon' => $cat['icon'],
                    'category_badge' => $cat['badge'],
                    'uom' => $mat['uom'] ?: 'PCS',
                    'unit_qty' => $unitQty,
                    'planned_material_qty' => $plannedMaterialQty,
                ];
            })->values()->toArray();

            return [
                'source' => 'staging',
                'item_code' => $itemCode,
                'fg_description' => MasterBom::where('parent_item', $itemCode)->value('parent_description'),
                'project_code' => null,
                'planned_qty' => $plannedQty,
                'materials' => $materials,
                'total_materials_count' => count($materials),
            ];
        }

        // 3. Jika tidak ada BOM sama sekali
        return [
            'source' => 'none',
            'item_code' => $itemCode,
            'fg_description' => null,
            'project_code' => null,
            'planned_qty' => $plannedQty,
            'materials' => [],
            'total_materials_count' => 0,
        ];
    }

    /**
     * Ambil riwayat log perubahan material (BOM lines) untuk suatu SPK
     */
    public function getChangeHistory(string $spkNumber, int $limit = 50)
    {
        return \App\Models\SpkBomChangeLog::where('spk_number', $spkNumber)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Update kuantitas plan_qty material
     */
    public function updateMaterialQty(string $spkNumber, string $itemCode, float $newPlanQty, ?float $baseQty = null, ?float $oldPlanQty = null, ?int $userId = null, ?string $userName = null): array
    {
        return app(SpkMasterService::class)->updateMaterialQty($spkNumber, $itemCode, $newPlanQty, $baseQty, $oldPlanQty, $userId, $userName);
    }

    /**
     * Tambah material baru ke SPK di SAP
     */
    public function addNewMaterial(string $spkNumber, string $itemCode, float $planQty, ?float $baseQty = null, ?string $warehouse = null, ?int $userId = null, ?string $userName = null): array
    {
        return app(SpkMasterService::class)->addNewMaterial($spkNumber, $itemCode, $planQty, $baseQty, $warehouse, $userId, $userName);
    }

    /**
     * Hapus material dari SPK di SAP (delete: true)
     */
    public function deleteMaterial(string $spkNumber, string $itemCode, ?int $userId = null, ?string $userName = null): array
    {
        return app(SpkMasterService::class)->deleteMaterial($spkNumber, $itemCode, $userId, $userName);
    }

    /**
     * Ganti material (Delete lama + Tambah baru)
     */
    public function replaceMaterial(string $spkNumber, string $oldItemCode, string $newItemCode, float $newPlanQty, ?float $baseQty = null, ?string $warehouse = null, ?int $userId = null, ?string $userName = null): array
    {
        return app(SpkMasterService::class)->replaceMaterial($spkNumber, $oldItemCode, $newItemCode, $newPlanQty, $baseQty, $warehouse, $userId, $userName);
    }
}
