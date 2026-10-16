<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvLineList extends Model
{
    use HasFactory;

    protected $table = 'inv_line_list';

    public $timestamps = false;

    protected $fillable = [
        'line_code',
        'line_name',
        'category',
        'area',
        'departement',
        'daily_minutes',
    ];
}
