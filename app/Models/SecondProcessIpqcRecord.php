<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondProcessIpqcRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
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
        'hour_ke' => 'integer',
        'output_qty' => 'integer',
        'sample_qty' => 'integer',
        'reject_sample_qty' => 'integer',
        'reject_rate' => 'float',
        'pass_qty' => 'integer',
        'reject_qty' => 'integer',
    ];

    public function report()
    {
        return $this->belongsTo(SecondProcessReport::class, 'report_id');
    }

    public function attachments()
    {
        return $this->morphMany(QcAttachment::class, 'attachable');
    }
}
