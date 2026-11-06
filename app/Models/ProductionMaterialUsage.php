<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionMaterialUsage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'production_material_usages';

    protected $fillable = [
        'outgoing_id',
        'item_code',
        'item_name',
        'pallet_id',
        'machine_no',
        'spk_code',
        'qty_used',
        'uom',
        'purpose',
        'used_at',
        'used_by',
        'used_by_name',
        'remarks',
    ];

    protected $casts = [
        'qty_used' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    public function outgoing()
    {
        return $this->belongsTo(MwhOutgoing::class, 'outgoing_id');
    }

    public function material()
    {
        return $this->belongsTo(MasterListMaterial::class, 'item_code', 'item_code');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}

