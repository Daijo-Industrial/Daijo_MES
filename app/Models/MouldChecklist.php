<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MouldChecklist extends Model
{
    use HasFactory;

    protected $table = 'mould_checklists';

    public const STANDARD_CHECK_POINTS = [
        1 => ['name' => 'COOLING CIRCULATION', 'cleaning' => '-', 'lubricate' => '-'],
        2 => ['name' => 'EJECTOR PIN', 'cleaning' => '-', 'lubricate' => '-'],
        3 => ['name' => 'EJECTOR BLOCK', 'cleaning' => '-', 'lubricate' => '-'],
        4 => ['name' => 'SLIDER', 'cleaning' => '-', 'lubricate' => '-'],
        5 => ['name' => 'ANGULAR PIN / ANGULAR CAMS', 'cleaning' => '-', 'lubricate' => '-'],
        6 => ['name' => 'LIFTER', 'cleaning' => '-', 'lubricate' => '-'],
        7 => ['name' => 'GAS SPRING / SPRING / CORE PULL', 'cleaning' => '-', 'lubricate' => '-'],
        8 => ['name' => 'AIR VENT', 'cleaning' => '-', 'lubricate' => '-'],
        9 => ['name' => 'LIMIT SWITCH / SENSOR', 'cleaning' => '-', 'lubricate' => '-'],
        10 => ['name' => 'EJECTOR LEADER PIN', 'cleaning' => '-', 'lubricate' => '-'],
        11 => ['name' => 'SUPPORT PIN', 'cleaning' => '-', 'lubricate' => '-'],
        12 => ['name' => 'LEADER PIN', 'cleaning' => '-', 'lubricate' => '-'],
        13 => ['name' => 'PULLER BOLTS / STOP BOLTS', 'cleaning' => '-', 'lubricate' => '-'],
        14 => ['name' => 'GUIDE RAIL', 'cleaning' => '-', 'lubricate' => '-'],
        15 => ['name' => 'CEK VISUAL CORE DAN CAVITY', 'cleaning' => '-', 'lubricate' => '-'],
        16 => ['name' => 'PEMBERIAN ANTI RUST CORE DAN CAVITY', 'cleaning' => '-', 'lubricate' => '-'],
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
        'start_at',
        'end_at',
        'is_ongoing',
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
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_ongoing' => 'boolean',
        'issued_at' => 'datetime',
        'checked_at' => 'datetime',
    ];

    public function getWorkScheduleFormattedAttribute(): string
    {
        if ($this->is_ongoing) {
            $startStr = $this->start_at 
                ? $this->start_at->translatedFormat('d M Y, H:i') 
                : ($this->check_date ? $this->check_date->format('d/m/Y') . ' ' . ($this->start_time ?: '') : '-');
            return "Mulai: {$startStr} (Ongoing)";
        }

        if ($this->start_at && $this->end_at) {
            if ($this->start_at->isSameDay($this->end_at)) {
                return $this->start_at->translatedFormat('d M Y') . ' (' . $this->start_at->format('H:i') . ' - ' . $this->end_at->format('H:i') . ')';
            }
            $diffDays = (int) $this->start_at->startOfDay()->diffInDays($this->end_at->startOfDay());
            $diffStr = $diffDays > 0 ? " (+{$diffDays} hari)" : '';
            return $this->start_at->translatedFormat('d M Y H:i') . ' s/d ' . $this->end_at->translatedFormat('d M Y H:i') . $diffStr;
        }

        if ($this->start_time && $this->end_time) {
            return ($this->check_date ? $this->check_date->format('d/m/Y') . ' ' : '') . "{$this->start_time} - {$this->end_time}";
        }

        return $this->check_date ? $this->check_date->format('d/m/Y') : '-';
    }

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

    public static function getPointCheckOptions(int $no): array
    {
        $item = self::STANDARD_CHECK_POINTS[$no] ?? null;
        if (! $item) {
            return [];
        }

        $name = $item['name'];
        if (! str_contains($name, ' / ')) {
            return [$name];
        }

        $parts = array_map('trim', explode(' / ', $name));

        return array_values(array_unique(array_merge([$name], $parts)));
    }
}
