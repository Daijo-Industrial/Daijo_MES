<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MasterBom extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'parent_item',
                'component_item',
                'quantity',
                'uom',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('master_bom');
    }

    protected $table = 'master_boms';

    protected $fillable = [
        'sap_line_id',
        'parent_item',
        'parent_description',
        'component_item',
        'component_description',
        'quantity',
        'uom',
        'family',
        'family_2',
        'is_active',
    ];

    protected $casts = [
        'quantity'  => 'float',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke komponen anak jika komponen ini adalah WIP / Semi-FG
     */
    public function subComponents()
    {
        return $this->hasMany(MasterBom::class, 'parent_item', 'component_item')
            ->where('is_active', true);
    }

    /**
     * Cek apakah komponen ini merupakan WIP (memiliki komponen turunan lagi)
     */
    public function getIsWipAttribute(): bool
    {
        return self::where('parent_item', $this->component_item)->exists();
    }

    /**
     * Deteksi kategori material berdasarkan kode item dan deskripsi
     */
    public static function determineCategory(string $itemCode, string $desc = '', bool $isWip = false): array
    {
        if ($isWip) {
            return [
                'type'   => 'WIP',
                'label'  => 'WIP / Sub-Assembly',
                'badge'  => 'bg-amber-100 text-amber-900 border-amber-300',
                'icon'   => '⚙️',
                'color'  => 'amber',
            ];
        }

        $code = strtoupper(trim($itemCode));

        // 0. Prioritas Utama: Cek jika ada Override Tipe Material manual oleh PE / Engineering
        $manualOverride = MasterBomMaterialOverride::getOverrideFor($code);
        if ($manualOverride) {
            return self::formatCategoryFromType($manualOverride);
        }

        $desc = strtoupper(trim($desc));

        // 1. Packaging / Kemasan (PRIORITAS UTAMA: Box, Partisi, Board, Bag, Karton, dsb)
        // Harus dicek sebelum resin karena banyak kemasan berbahan PP seperti 'BOX PP BOARD'
        if (str_starts_with($code, '600-') || str_starts_with($code, '601-') || str_starts_with($code, '602-') || str_starts_with($code, '603-') || str_starts_with($code, '610-') || str_starts_with($code, '612-')
            || str_contains($desc, 'BOX') || str_contains($desc, 'PARTISI') || str_contains($desc, 'CARTON') || str_contains($desc, 'KARTON') 
            || str_contains($desc, 'BOARD') || str_contains($desc, 'PACKAGING') || str_contains($desc, 'PACK') || str_contains($desc, 'BAG') 
            || str_contains($desc, 'POLYBAG') || str_contains($desc, 'TAPE') || str_contains($desc, 'ISOLASI') || str_contains($desc, 'BUBBLE') 
            || str_contains($desc, 'FOAM') || str_contains($desc, 'LAYER') || str_contains($desc, 'PAD') || str_contains($desc, 'LABEL') 
            || str_contains($desc, 'STICKER') || str_contains($desc, 'STIKER') || str_contains($desc, 'USER GUIDE') || str_contains($desc, 'MANUAL') 
            || str_contains($desc, 'WARRANTY') || str_contains($desc, 'BARCODE')) {
            return [
                'type'   => 'PACKAGING',
                'label'  => 'Packaging / Kemasan',
                'badge'  => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                'icon'   => '📦',
                'color'  => 'emerald',
            ];
        }

        // 2. Cat & Chemical (Thinner, Hardener, Cat, Primer, Tinta, dsb)
        if (str_starts_with($code, '440-') || str_starts_with($code, '450-') || str_starts_with($code, '458-') 
            || str_contains($desc, 'PAINT') || str_contains($desc, 'THINNER') || str_contains($desc, 'HARDENER') || str_contains($desc, 'PRIMER')
            || str_contains($desc, 'CAT ') || str_contains($desc, 'TINTA') || str_contains($desc, 'INK') || str_contains($desc, 'SOLVENT') || str_contains($desc, 'CLEAR')) {
            return [
                'type'   => 'CHEMICAL',
                'label'  => 'Cat / Chemical',
                'badge'  => 'bg-cyan-100 text-cyan-900 border-cyan-300',
                'icon'   => '🧪',
                'color'  => 'cyan',
            ];
        }

        // 3. Biji Plastik / Resin (ABS, GPPS Styron, PP Resin, HIPS, PC, Poly, dsb)
        if (str_starts_with($code, '400-') || str_starts_with($code, '401-') || str_starts_with($code, 'ZGPS-')
            || str_contains($desc, 'RESIN') || str_contains($desc, 'ABS ') || str_contains($desc, 'ABS-') || str_contains($desc, 'STYRON') 
            || str_contains($desc, 'GPPS') || str_contains($desc, 'HIPS') || str_contains($desc, 'CMX') || str_contains($desc, 'PP RESIN') 
            || str_contains($desc, 'PP COMPOUND') || str_contains($desc, 'PP HOMOPOLYMER') || str_contains($desc, 'PP ')
            || str_contains($desc, 'POLYM') || str_contains($desc, 'POLYPROPYLENE') || str_contains($desc, 'POLYETHYLENE') 
            || str_contains($desc, 'POLYCARBONATE') || str_contains($desc, 'PC/ABS') || str_contains($desc, 'POM ') || str_contains($desc, 'NYLON') 
            || str_contains($desc, 'PELLETS') || str_contains($desc, 'BIJI PLASTIK')) {
            return [
                'type'   => 'RESIN',
                'label'  => 'Biji Plastik / Resin',
                'badge'  => 'bg-purple-100 text-purple-900 border-purple-300',
                'icon'   => '🧬',
                'color'  => 'purple',
            ];
        }

        // 4. Hardware / Fasteners (Screw, Bolt, Nut, Pin, Washer, dsb)
        if (str_starts_with($code, '300-') || str_starts_with($code, '301-') || str_starts_with($code, '302-') || str_starts_with($code, '310-') || str_starts_with($code, '311-')
            || str_contains($desc, 'SCREW') || str_contains($desc, 'BOLT') || str_contains($desc, 'NUT') || str_contains($desc, 'WASHER') 
            || str_contains($desc, 'SPRING') || str_contains($desc, 'PIN') || str_contains($desc, 'RIVET') || str_contains($desc, 'BAUT') || str_contains($desc, 'SEKRUP') || str_contains($desc, 'MUR')) {
            return [
                'type'   => 'HARDWARE',
                'label'  => 'Hardware / Fastener',
                'badge'  => 'bg-blue-100 text-blue-900 border-blue-300',
                'icon'   => '🔩',
                'color'  => 'blue',
            ];
        }

        return [
            'type'   => 'RAW_MATERIAL',
            'label'  => 'Material / Part',
            'badge'  => 'bg-slate-100 text-slate-800 border-slate-300',
            'icon'   => '🧩',
            'color'  => 'slate',
        ];
    }

    /**
     * Format array kategori dan styling badge berdasarkan kode tipe (RAW_MATERIAL, RESIN, CHEMICAL, HARDWARE, PACKAGING, WIP)
     */
    public static function formatCategoryFromType(string $type): array
    {
        return match (strtoupper(trim($type))) {
            'PACKAGING' => [
                'type'   => 'PACKAGING',
                'label'  => 'Packaging / Kemasan',
                'badge'  => 'bg-emerald-100 text-emerald-900 border-emerald-300',
                'icon'   => '📦',
                'color'  => 'emerald',
            ],
            'CHEMICAL' => [
                'type'   => 'CHEMICAL',
                'label'  => 'Cat / Chemical',
                'badge'  => 'bg-cyan-100 text-cyan-900 border-cyan-300',
                'icon'   => '🧪',
                'color'  => 'cyan',
            ],
            'RESIN' => [
                'type'   => 'RESIN',
                'label'  => 'Biji Plastik / Resin',
                'badge'  => 'bg-purple-100 text-purple-900 border-purple-300',
                'icon'   => '🧬',
                'color'  => 'purple',
            ],
            'HARDWARE' => [
                'type'   => 'HARDWARE',
                'label'  => 'Hardware / Fastener',
                'badge'  => 'bg-blue-100 text-blue-900 border-blue-300',
                'icon'   => '🔩',
                'color'  => 'blue',
            ],
            'WIP' => [
                'type'   => 'WIP',
                'label'  => 'WIP / Sub-Assembly',
                'badge'  => 'bg-amber-100 text-amber-900 border-amber-300',
                'icon'   => '⚙️',
                'color'  => 'amber',
            ],
            default => [
                'type'   => 'RAW_MATERIAL',
                'label'  => 'Material / Part',
                'badge'  => 'bg-slate-100 text-slate-800 border-slate-300',
                'icon'   => '🧩',
                'color'  => 'slate',
            ],
        };
    }

    /**
     * Method rekursif untuk meledakkan (Explode) BOM hingga level terbawah
     *
     * @param string $parentItem Kode part parent/FG
     * @param float $multiplier Jumlah target yang ingin dibuat (default 1)
     * @param int $level Kedalaman level hierarki (0 = Top/FG)
     * @return array
     */
    public static function explodeTree(string $parentItem, float $multiplier = 1.0, int $level = 0): array
    {
        $components = self::where('parent_item', $parentItem)
            ->where('is_active', true)
            ->get();

        $tree = [];

        foreach ($components as $comp) {
            $requiredQty = $comp->quantity * $multiplier;
            $hasChildren = self::where('parent_item', $comp->component_item)->exists();
            $category = self::determineCategory($comp->component_item, $comp->component_description ?? '', $hasChildren);

            $node = [
                'level'                 => $level + 1,
                'parent_item'           => $comp->parent_item,
                'component_item'        => $comp->component_item,
                'component_description' => $comp->component_description,
                'unit_qty'              => $comp->quantity,
                'total_qty'             => $requiredQty,
                'uom'                   => $comp->uom,
                'is_wip'                => $hasChildren,
                'category'              => $category,
                'children'              => [],
            ];

            // Jika item ini punya turunan (WIP), telusuri anak-anaknya secara rekursif
            if ($hasChildren) {
                $node['children'] = self::explodeTree($comp->component_item, $requiredQty, $level + 1);
            }

            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * Menghitung ringkasan kebutuhan bersih material (Flattened Net Requirement)
     */
    public static function getFlattenedSummary(string $parentItem, float $multiplier = 1.0): array
    {
        $tree = self::explodeTree($parentItem, $multiplier);
        $materials = [];
        $wips = [];
        $maxDepth = 1;

        $collect = function ($nodes) use (&$collect, &$materials, &$wips, &$maxDepth) {
            foreach ($nodes as $node) {
                if ($node['level'] > $maxDepth) {
                    $maxDepth = $node['level'];
                }

                if ($node['is_wip']) {
                    $key = $node['component_item'];
                    if (!isset($wips[$key])) {
                        $wips[$key] = [
                            'item_code'   => $node['component_item'],
                            'description' => $node['component_description'],
                            'total_qty'   => 0,
                            'uom'         => $node['uom'],
                            'category'    => $node['category'],
                        ];
                    }
                    $wips[$key]['total_qty'] += $node['total_qty'];
                    $collect($node['children']);
                } else {
                    $key = $node['component_item'];
                    if (!isset($materials[$key])) {
                        $materials[$key] = [
                            'item_code'   => $node['component_item'],
                            'description' => $node['component_description'],
                            'total_qty'   => 0,
                            'uom'         => $node['uom'],
                            'category'    => $node['category'],
                        ];
                    }
                    $materials[$key]['total_qty'] += $node['total_qty'];
                }
            }
        };

        $collect($tree);

        return [
            'tree'        => $tree,
            'materials'   => array_values($materials),
            'wips'        => array_values($wips),
            'max_depth'   => $maxDepth,
            'total_items' => count($materials) + count($wips),
        ];
    }
}
