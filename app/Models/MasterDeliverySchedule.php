<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterDeliverySchedule extends Model
{
    use HasFactory;

    protected $table = 'master_delivery_schedule';

    protected $fillable = [
        'customer_code',
        'item_code',
        'tanggal',
        'quantity',
        'so_num',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'quantity' => 'integer',
    ];
}
