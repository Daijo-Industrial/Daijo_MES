<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WmsPosition extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rack_id', 'level_no', 'slot_no', 'customer_code',
        'position_code', 'status', 'last_item_code', 'max_capacity',
    ];

    public function rack()
    {
        return $this->belongsTo(WmsRack::class, 'rack_id');
    }

    public function palletForms()
    {
        return $this->hasMany(WmsPalletForm::class, 'position_id');
    }

    public function customer()
    {
        return $this->belongsTo(MasterCustomerDelivery::class, 'customer_code', 'customer_code');
    }

    public function getDistanceScoreAttribute(): int
    {
        $base = $this->rack?->distance_score ?? 9999;
        $slotPenalty = max(0, 14 - ($this->slot_no ?? 1)) * 4;
        $levelPenalty = (($this->level_no ?? 1) - 1) * 3;

        return $base + $slotPenalty + $levelPenalty;
    }
}
