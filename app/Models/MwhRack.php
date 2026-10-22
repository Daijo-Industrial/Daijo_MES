<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MwhRack extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mwh_racks';

    protected $fillable = ['whse_id', 'rack_code'];

    public function warehouse()
    {
        return $this->belongsTo(MwhWarehouse::class, 'whse_id');
    }

    public function positions()
    {
        return $this->hasMany(MwhPosition::class, 'rack_id');
    }
}
