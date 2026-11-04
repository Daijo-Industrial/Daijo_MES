<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecondProcessDefectCatalog extends Model
{
    use HasFactory;

    protected $table = 'second_process_defect_catalogs';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($catalog) {
            $catalog->name = strtoupper(trim((string) $catalog->name));
        });
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(SecondProcessDefectAlias::class, 'defect_catalog_id');
    }

    public function ngRecords(): HasMany
    {
        return $this->hasMany(SecondProcessNgRecord::class, 'ng_name', 'name');
    }

    public static function activeNames(): array
    {
        return static::where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }
}
