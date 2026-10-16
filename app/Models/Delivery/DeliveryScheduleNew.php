<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryScheduleNew extends Model
{
    use HasFactory;

    protected $table = 'delivery_schedule_new';

    protected $fillable = [
        'code',
        'so_number',
        'customer_code',
        'delivery_date',
        'item_code',
        'delivery_quantity',
    ];
}
