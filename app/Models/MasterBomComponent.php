<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterBomComponent extends Model
{
    use HasFactory;

    protected $table = 'master_bom_components';

    protected $fillable = [
        'fg_id',
        'parent_item',
        'component_item',
        'component_description',
        'depth_level',
        'item_type',
        'is_wip',
        'unit_qty',
        'total_qty_per_fg',
        'uom',
        'lineage_path',
    ];

    protected $casts = [
        'depth_level' => 'integer',
        'is_wip' => 'boolean',
        'unit_qty' => 'float',
        'total_qty_per_fg' => 'float',
    ];

    /**
     * Relasi ke Header Finished Goods induk
     */
    public function fgHeader(): BelongsTo
    {
        return $this->belongsTo(MasterBomFgHeader::class, 'fg_id');
    }
}
