<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterBomFgHeader extends Model
{
    use HasFactory;

    protected $table = 'master_bom_fg_headers';

    protected $fillable = [
        'fg_item_code',
        'fg_description',
        'project_code',
        'family',
        'customer_name',
        'uom',
        'total_wip_count',
        'total_raw_count',
        'max_depth_level',
        'has_packaging',
        'is_verified',
        'verified_at',
        'verified_by_name',
        'is_active',
    ];

    protected $casts = [
        'total_wip_count' => 'integer',
        'total_raw_count' => 'integer',
        'max_depth_level' => 'integer',
        'has_packaging' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Seluruh komponen bertingkat di bawah FG ini
     */
    public function components(): HasMany
    {
        return $this->hasMany(MasterBomComponent::class, 'fg_id');
    }

    /**
     * Khusus part WIP / Sub-Assembly yang butuh SPK produksi
     */
    public function wipComponents(): HasMany
    {
        return $this->hasMany(MasterBomComponent::class, 'fg_id')->where('is_wip', true);
    }

    /**
     * Khusus bahan baku / chemical / packaging yang ditarik dari gudang
     */
    public function rawMaterials(): HasMany
    {
        return $this->hasMany(MasterBomComponent::class, 'fg_id')->where('is_wip', false);
    }
}
