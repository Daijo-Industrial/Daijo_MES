<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpProductionEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'recorded_at',
        'good_qty',
        'remarks',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(SpProductionSession::class, 'session_id');
    }
}
