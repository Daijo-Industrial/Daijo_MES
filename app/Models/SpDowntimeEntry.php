<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpDowntimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'reason',
        'category',
        'start_time',
        'resume_time',
        'duration_minutes',
        'remarks',
        'countermeasure',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'resume_time' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(SpProductionSession::class, 'session_id');
    }

    public function calculateDuration(): int
    {
        if ($this->start_time && $this->resume_time) {
            return (int) $this->start_time->diffInMinutes($this->resume_time);
        }

        return 0;
    }
}
