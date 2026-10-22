<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DicAccessoryLot extends Model
{
    use HasFactory;

    protected $table = 'dic_accessory_lots';

    protected $fillable = [
        'dic_id',
        'accessory_name',
        'accessory_lot',
    ];

    public function dailyItemCode()
    {
        return $this->belongsTo(DailyItemCode::class, 'dic_id');
    }
}
