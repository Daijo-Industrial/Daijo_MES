<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpRejectEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'defect_type',
        'quantity',
        'cause',
        'remarks',
    ];

    public function session()
    {
        return $this->belongsTo(SpProductionSession::class, 'session_id');
    }
}
