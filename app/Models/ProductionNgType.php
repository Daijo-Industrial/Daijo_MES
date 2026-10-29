<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionNgType extends Model
{
    use HasFactory;

    protected $table = 'production_ng_types';

    protected $fillable = [
        'ng_type',
    ];

    /**
     * Relasi: satu NG Type punya banyak NG Detail
     */
    public function ngDetails()
    {
        return $this->hasMany(ProductionNgDetail::class, 'ng_type_id');
    }

    public function adjustMachineNgDetails()
    {
        return $this->hasMany(AdjustMachineNgDetail::class, 'ng_type_id');
    }
}
