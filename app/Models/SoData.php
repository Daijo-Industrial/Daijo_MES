<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoData extends Model
{
    use HasFactory;

    protected $table = 'so_datas';

    // public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'doc_num',
        'customer',
        'posting_date',
        'item_code',
        'item_name',
        'quantity',
        'sales_uom',
        'packaging_quantity',
        'sales_pack',
        'is_finish',
        'is_done',
        'create_date',
        'update_date',
        'update_fulltime',
    ];

    public function scannedData()
    {
        return $this->hasMany(ScannedData::class, 'doc_num', 'doc_num');
    }
}
