<?php

namespace App\Models\Production;

use Illuminate\Database\Eloquent\Model;

class PRD_BrokenChild extends Model
{
    protected $table = 'prd_broken_childs';

    protected $fillable = [
        'child_id',
        'broken_quantity',
        'remark',
    ];
}
