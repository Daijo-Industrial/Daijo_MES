<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceCheckDetail extends Model
{
    use HasFactory;

    protected $table = 'maintenance_check_details';

    protected $fillable = [
        'header_id',
        'item_id',
        'value',
        'is_normal',
        'remarks',
    ];

    public function header()
    {
        return $this->belongsTo(MaintenanceCheckHeader::class, 'header_id');
    }

    public function item()
    {
        return $this->belongsTo(MaintenanceCheckItem::class, 'item_id');
    }
}
