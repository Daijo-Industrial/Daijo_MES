<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WmsWarehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['whse_code', 'whse_name', 'layout_image', 'exit_location'];

    public function racks()
    {
        return $this->hasMany(WmsRack::class, 'whse_id');
    }
}
