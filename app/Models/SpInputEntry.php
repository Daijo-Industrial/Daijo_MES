<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpInputEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'quantity',
        'source',
        'pallet_number',
        'remarks',
    ];

    public function session()
    {
        return $this->belongsTo(SpProductionSession::class, 'session_id');
    }
}
