<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpqcInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'part_number',
        'shift',
        'unit_line',
        'process_prod',
        'model',
        'part_name',
        'customer',
        'lot_color',
        'std_glossy',
        'std_viscosity',
        'std_oven_temp',
        'product_color',
        'app_sample',
        'selected_measurements',
        'total_output',
        'total_sample',
        'total_reject_sample',
        'total_reject_rate',
        'total_pass',
        'total_reject',
        'inspector_name',
        'checker_name',
        'overall_judgement',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'selected_measurements' => 'array',
    ];

    public function records()
    {
        return $this->hasMany(IpqcInspectionRecord::class, 'inspection_id');
    }

    public function attachments()
    {
        return $this->morphMany(QcAttachment::class, 'attachable');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
