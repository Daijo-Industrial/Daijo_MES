<?php

namespace App\Models\Inspection;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionDefect extends Model
{
    use HasFactory;

    protected $table = 'inspection_defects';

    protected $fillable = [
        'name',
        'department',
    ];
}
