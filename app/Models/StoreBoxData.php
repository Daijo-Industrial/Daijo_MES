<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreBoxData extends Model
{
    use HasFactory;

    protected $table = 'store_box_data';

    protected $fillable = [
        'part_no',
        'part_name',
    ];

    public function details()
    {
        return $this->hasMany(StoreBoxDetail::class, 'part_no', 'part_no');
    }
}
