<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonePengawas extends Model
{
    use HasFactory;

    protected $table = 'zone_pengawas';

    protected $fillable = [
        'zone_id',
        'shift',
        'pengawas',
        'start_date',
        'end_date',
    ];

    public function pengawasUser()
    {
        return $this->belongsTo(OperatorUser::class, 'pengawas', 'name');
    }
}
