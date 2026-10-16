<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterZone extends Model
{
    protected $table = 'master_zone';

    protected $fillable = [
        'zone_name',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'zone_id');
    }

    public function zoneData()
    {
        return $this->hasMany(ZonePengawas::class, 'zone_id');
    }
}
