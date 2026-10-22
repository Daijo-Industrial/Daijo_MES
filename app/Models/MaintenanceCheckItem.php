<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceCheckItem extends Model
{
    use HasFactory;

    protected $table = 'maintenance_check_items';

    protected $fillable = [
        'item_name',
        'period',
        'kriteria',
        'standard',
        'input_type',
        'unit',
        'sort_order',
    ];

    public function details()
    {
        return $this->hasMany(MaintenanceCheckDetail::class, 'item_id');
    }
}
