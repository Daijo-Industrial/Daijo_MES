<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorUser extends Model
{
    use HasFactory;

    protected $table = 'operator_user';

    protected $fillable = [
        'name',
        'password',
        'profile_picture',
        'department',
        'position',
    ];
}
