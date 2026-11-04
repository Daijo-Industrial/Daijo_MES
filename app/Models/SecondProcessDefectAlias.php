<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecondProcessDefectAlias extends Model
{
    use HasFactory;

    protected $table = 'second_process_defect_aliases';

    protected $fillable = [
        'defect_catalog_id',
        'alias',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->alias = strtoupper(trim((string) $model->alias));
        });
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(SecondProcessDefectCatalog::class, 'defect_catalog_id');
    }

    /**
     * Resolve any raw defect name to its canonical name.
     * Checks alias mappings first, then canonical catalogs, and falls back to uppercase raw.
     */
    public static function resolveCanonical(?string $rawName): ?string
    {
        if ($rawName === null || trim($rawName) === '') {
            return null;
        }

        $clean = strtoupper(trim($rawName));

        // Check alias mapping
        $alias = static::with('catalog')->where('alias', $clean)->first();
        if ($alias && $alias->catalog) {
            return $alias->catalog->name;
        }

        // Check if already canonical
        $catalog = SecondProcessDefectCatalog::where('name', $clean)->first();
        if ($catalog) {
            return $catalog->name;
        }

        return $clean;
    }
}
