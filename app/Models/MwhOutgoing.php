<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MwhOutgoing extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mwh_outgoings';

    protected $fillable = [
        'whse_id',
        'outgoing_code',
        'pallet_id',
        'position_id',
        'item_code',
        'qty_taken',
        'uom',
        'outgoing_date',
        'issued_to',
        'remarks',
    ];

    protected $casts = [
        'qty_taken' => 'decimal:2',
        'outgoing_date' => 'date',
    ];

    public function warehouse()
    {
        return $this->belongsTo(MwhWarehouse::class, 'whse_id');
    }

    public function pallet()
    {
        return $this->belongsTo(MwhPallet::class, 'pallet_id', 'pallet_id');
    }

    public function position()
    {
        return $this->belongsTo(MwhPosition::class, 'position_id');
    }

    public function material()
    {
        return $this->belongsTo(MasterListMaterial::class, 'item_code', 'item_code');
    }
}
