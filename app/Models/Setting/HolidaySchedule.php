<?php

namespace App\Models\Setting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidaySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'description',
        'injection',
        'second_process',
        'assembly',
        'moulding',
        'half_day',
    ];
}
