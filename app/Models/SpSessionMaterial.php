<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpSessionMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'type',
        'item_name',
        'lot_number',
        'visco',
        'mixing_ratio',
        'qty',
        'uom',
    ];

    public function session()
    {
        return $this->belongsTo(SpProductionSession::class, 'session_id');
    }
}
