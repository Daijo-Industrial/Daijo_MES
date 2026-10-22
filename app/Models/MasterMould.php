<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterMould extends Model
{
    use HasFactory;

    protected $table = 'master_moulds';

    protected $fillable = [
        'mould_no',
        'part_no',
        'part_name',
        'model',
        'customer',
        'machine_number',
        'material',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function checklists()
    {
        return $this->hasMany(MouldChecklist::class, 'mould_id');
    }
}
