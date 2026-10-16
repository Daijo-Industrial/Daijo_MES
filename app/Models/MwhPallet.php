<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MwhPallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mwh_pallets';

    protected $fillable = [
        'whse_id',
        'pallet_id',
        'incoming_header_id',
        'item_code',
        'lot_no',
        'initial_qty',
        'current_qty',
        'uom',
        'position_id',
        'initial_position_id',
        'status',
        'is_qc_hold',
        'qc_hold_reason',
        'created_at',
    ];

    protected $casts = [
        'initial_qty' => 'decimal:2',
        'current_qty' => 'decimal:2',
        'is_qc_hold' => 'boolean',
    ];

    public function warehouse()
    {
        return $this->belongsTo(MwhWarehouse::class, 'whse_id');
    }

    public function incomingHeader()
    {
        return $this->belongsTo(MwhIncomingHeader::class, 'incoming_header_id');
    }

    public function material()
    {
        return $this->belongsTo(MasterListMaterial::class, 'item_code', 'item_code');
    }

    public function position()
    {
        return $this->belongsTo(MwhPosition::class, 'position_id')->withTrashed();
    }

    public function initialPosition()
    {
        return $this->belongsTo(MwhPosition::class, 'initial_position_id')->withTrashed();
    }

    public function outgoings()
    {
        return $this->hasMany(MwhOutgoing::class, 'pallet_id', 'pallet_id');
    }

    /**
     * Check if this pallet is currently in the temporary area (unassigned slot).
     */
    public function isTemporary(): bool
    {
        return is_null($this->position_id) && $this->current_qty > 0;
    }

    /**
     * Accessor for is_temporary attribute.
     */
    public function getIsTemporaryAttribute(): bool
    {
        return $this->isTemporary();
    }

    /**
     * Dynamically compute live pallet status based on current_qty vs initial_qty.
     */
    public function getStatusAttribute($value): string
    {
        if ($this->current_qty <= 0) {
            return 'EMPTY';
        }
        if ($this->current_qty < $this->initial_qty) {
            return 'PARTIAL';
        }

        return $value ?: 'STORED';
    }
}
