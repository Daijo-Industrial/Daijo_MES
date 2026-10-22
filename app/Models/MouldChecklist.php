<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MouldChecklist extends Model
{
    use HasFactory;

    protected $table = 'mould_checklists';

    public const STANDARD_CHECK_POINTS = [
        1 => ['name' => 'COOLING CIRCULATION', 'cleaning' => 'OK', 'lubricate' => '-'],
        2 => ['name' => 'EJECTOR PIN', 'cleaning' => 'OK', 'lubricate' => 'OK'],
        3 => ['name' => 'EJECTOR BLOCK', 'cleaning' => 'OK', 'lubricate' => 'OK'],
        4 => ['name' => 'SLIDER', 'cleaning' => 'OK', 'lubricate' => 'OK'],
        5 => ['name' => 'ANGULAR PIN / ANGULAR CAMS', 'cleaning' => 'OK', 'lubricate' => 'OK'],
        6 => ['name' => 'LIFTER', 'cleaning' => '-', 'lubricate' => '-'],
        7 => ['name' => 'GAS SPRING / SPRING / SLIDER', 'cleaning' => 'OK', 'lubricate' => '-'],
        8 => ['name' => 'AIR VENT', 'cleaning' => 'OK', 'lubricate' => '-'],
        9 => ['name' => 'LIMIT SWITCH / SENSOR', 'cleaning' => '-', 'lubricate' => '-'],
        10 => ['name' => 'EJECTOR LEADER PIN', 'cleaning' => 'OK', 'lubricate' => 'OK'],
        11 => ['name' => 'SUPPORT PIN', 'cleaning' => 'OK', 'lubricate' => 'OK'],
        12 => ['name' => 'LEADER PIN', 'cleaning' => 'OK', 'lubricate' => 'OK'],
        13 => ['name' => 'PULLER BOLTS / STOP BOLTS', 'cleaning' => '-', 'lubricate' => '-'],
        14 => ['name' => 'GUIDE RAIL', 'cleaning' => 'OK', 'lubricate' => 'OK'],
        15 => ['name' => 'CEK VISUAL CORE DAN CAVITY', 'cleaning' => 'OK', 'lubricate' => '-'],
        16 => ['name' => 'PEMBERIAN ANTI RUST CORE DAN CAVITY', 'cleaning' => 'OK', 'lubricate' => '-'],
    ];

    protected $fillable = [
        'doc_number',
        'revision',
        'check_date',
        'mould_id',
        'mould_no',
        'part_no',
        'part_name',
        'model',
        'customer',
        'machine_number',
        'technician_name',
        'start_time',
        'end_time',
        'status',
        'issued_by_id',
        'issued_name',
        'issued_at',
        'checked_by_id',
        'checked_name',
        'checked_at',
        'rejection_note',
        'created_by',
    ];

    protected $casts = [
        'check_date' => 'date',
        'issued_at' => 'datetime',
        'checked_at' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(MouldChecklistDetail::class, 'mould_checklist_id')->orderBy('item_no');
    }

    public function masterMould()
    {
        return $this->belongsTo(MasterMould::class, 'mould_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
