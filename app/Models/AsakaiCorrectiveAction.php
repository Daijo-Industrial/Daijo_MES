<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsakaiCorrectiveAction extends Model
{
    protected $table = 'asakai_corrective_actions';

    protected $fillable = [
        'asakai_id',
        'action',
        'order_no',
    ];

    protected $casts = [
        'order_no' => 'integer',
    ];

    public function asakai()
    {
        return $this->belongsTo(Asakai::class, 'asakai_id');
    }

    public function getActionNumberAttribute()
    {
        return "Action {$this->order_no}";
    }
}
