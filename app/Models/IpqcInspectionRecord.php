<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpqcInspectionRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_id',
        'hour_ke',
        'appearance_checks',
        'condition_checks',
        'measurements',
        'fitting_test',
        'tape_test_judgement',
        'output_qty',
        'sample_qty',
        'reject_sample_qty',
        'reject_rate',
        'pass_qty',
        'reject_qty',
        'judgement',
    ];

    protected $casts = [
        'appearance_checks' => 'array',
        'condition_checks' => 'array',
        'measurements' => 'array',
    ];

    public function inspection()
    {
        return $this->belongsTo(IpqcInspection::class, 'inspection_id');
    }

    public function attachments()
    {
        return $this->morphMany(QcAttachment::class, 'attachable');
    }
}
