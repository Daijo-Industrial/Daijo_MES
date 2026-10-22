<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MwhWarehouse extends Model
{
    use BelongsToBranch, HasFactory, SoftDeletes;

    protected $table = 'mwh_warehouses';

    protected $fillable = ['branch_id', 'whse_code', 'whse_name'];

    public function racks()
    {
        return $this->hasMany(MwhRack::class, 'whse_id');
    }

    public function incomingHeaders()
    {
        return $this->hasMany(MwhIncomingHeader::class, 'whse_id');
    }

    public function pallets()
    {
        return $this->hasMany(MwhPallet::class, 'whse_id');
    }

    public function outgoings()
    {
        return $this->hasMany(MwhOutgoing::class, 'whse_id');
    }
}
