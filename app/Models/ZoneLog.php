<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoneLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id', 'pengawas', 'start_date', 'end_date', 'shift',
    ];

    public function zone()
    {
        return $this->belongsTo(MasterZone::class, 'zone_id');
    }

    public function pengawasUser()
    {
        return $this->belongsTo(OperatorUser::class, 'pengawas', 'name');
    }
}
