<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpkItemHistory extends Model
{
    use HasFactory;

    protected $table = 'spk_item_histories';

    protected $fillable = [
        'spk_number',
        'item_code',
    ];
}
