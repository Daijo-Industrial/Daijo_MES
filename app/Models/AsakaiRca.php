<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsakaiRca extends Model
{
    protected $table = 'asakai_rca';

    protected $fillable = [
        'asakai_id',
        'why_level',
        'description',
        'order_no',
    ];

    protected $casts = [
        'why_level' => 'integer',
        'order_no' => 'integer',
    ];

    public function asakai()
    {
        return $this->belongsTo(Asakai::class, 'asakai_id');
    }

    public function getWhyLabelAttribute()
    {
        return "Why {$this->why_level}";
    }
}
