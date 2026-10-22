<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MwhPosition extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mwh_positions';

    protected $fillable = [
        'rack_id',
        'level_no',
        'slot_no',
        'position_code',
        'slot_label',
        'status',
        'last_item_code',
        'max_capacity',
    ];

    public function rack()
    {
        return $this->belongsTo(MwhRack::class, 'rack_id');
    }

    public function pallets()
    {
        return $this->hasMany(MwhPallet::class, 'position_id');
    }
}
