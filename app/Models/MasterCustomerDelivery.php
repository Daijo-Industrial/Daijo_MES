<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterCustomerDelivery extends Model
{
    use HasFactory;

    protected $table = 'master_customer_delivery';

    protected $fillable = [
        'customer_code',
        'customer_name',
    ];
}
