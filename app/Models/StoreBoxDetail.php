<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreBoxDetail extends Model
{
    use HasFactory;

    protected $table = 'store_box_details';

    protected $fillable = [
        'part_no',
        'label',
        'status',
        'remark',
    ];

    public function boxData()
    {
        return $this->belongsTo(StoreBoxData::class, 'part_no', 'part_no');
    }
}
