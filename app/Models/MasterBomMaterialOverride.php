<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBomMaterialOverride extends Model
{
    use HasFactory;

    protected $table = 'master_bom_material_overrides';

    protected $fillable = [
        'item_code',
        'item_type',
        'updated_by',
        'updated_by_name',
    ];

    protected static ?array $cache = null;

    /**
     * Cache/lookup override type for an item code
     */
    public static function getOverrideFor(string $itemCode): ?string
    {
        if (self::$cache === null) {
            try {
                self::$cache = self::pluck('item_type', 'item_code')->toArray();
            } catch (\Throwable $e) {
                return null;
            }
        }

        $code = strtoupper(trim($itemCode));

        return self::$cache[$code] ?? null;
    }

    /**
     * Set or update override for an item code
     */
    public static function setOverride(string $itemCode, string $itemType, ?int $userId = null, ?string $userName = null): self
    {
        $code = strtoupper(trim($itemCode));
        $record = self::updateOrCreate(
            ['item_code' => $code],
            [
                'item_type' => $itemType,
                'updated_by' => $userId,
                'updated_by_name' => $userName,
            ]
        );

        if (self::$cache !== null) {
            self::$cache[$code] = $itemType;
        }

        return $record;
    }
}
