<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpReworkEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'input_qty',
        'recovered_qty',
        'scrapped_qty',
        'remarks',
    ];

    public function session()
    {
        return $this->belongsTo(SpProductionSession::class, 'session_id');
    }
}
