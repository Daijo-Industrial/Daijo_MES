<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MouldChecklistDetail extends Model
{
    use HasFactory;

    protected $table = 'mould_checklist_details';

    protected $fillable = [
        'mould_checklist_id',
        'item_no',
        'point_check',
        'cleaning_status',
        'lubricate_status',
        'remark',
    ];

    public function checklist()
    {
        return $this->belongsTo(MouldChecklist::class, 'mould_checklist_id');
    }
}
