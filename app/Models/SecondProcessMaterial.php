<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondProcessMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'type',
        'item_name',
        'lot_number',
        'visco',
        'qty',
        'uom',
        'mixing_ratio',
        'paint_type',
        'sub_type',
    ];

    protected $casts = [
        'qty' => 'float',
    ];

    public function report()
    {
        return $this->belongsTo(SecondProcessReport::class, 'report_id');
    }
}
