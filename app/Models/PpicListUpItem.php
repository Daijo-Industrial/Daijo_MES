<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PpicListUpItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_up_id',
        'machine_id',
        'machine_name',
        'part_no',
        'description',
        'material_type',
        'spk_no',
        'cavity',
        'cycle_time',
        'target_per_hour',
        'operator_shift_1',
        'operator_shift_2',
        'operator_shift_3',
        'qty_to_run',
        'reason',
        'priority',
        'is_generated',
    ];

    protected $casts = [
        'is_generated' => 'boolean',
        'cavity' => 'integer',
        'cycle_time' => 'integer',
        'target_per_hour' => 'integer',
        'operator_shift_1' => 'integer',
        'operator_shift_2' => 'integer',
        'operator_shift_3' => 'integer',
        'qty_to_run' => 'integer',
        'priority' => 'integer',
    ];

    public function listUp()
    {
        return $this->belongsTo(PpicListUp::class, 'list_up_id');
    }

    public function machine()
    {
        return $this->belongsTo(User::class, 'machine_id');
    }

    public function masterItem()
    {
        return $this->belongsTo(MasterListItem::class, 'part_no', 'item_code');
    }

    /**
     * Active shifts count based on operator assignment
     */
    public function getActiveShiftsCountAttribute(): int
    {
        $count = 0;
        if ($this->operator_shift_1 > 0) {
            $count++;
        }
        if ($this->operator_shift_2 > 0) {
            $count++;
        }
        if ($this->operator_shift_3 > 0) {
            $count++;
        }

        return $count;
    }

    /**
     * Quantity allocated per active shift
     */
    public function getQtyPerShiftAttribute(): array
    {
        $activeShifts = [];
        if ($this->operator_shift_1 > 0) {
            $activeShifts[] = 1;
        }
        if ($this->operator_shift_2 > 0) {
            $activeShifts[] = 2;
        }
        if ($this->operator_shift_3 > 0) {
            $activeShifts[] = 3;
        }

        $count = count($activeShifts);
        if ($count === 0 || $this->qty_to_run <= 0) {
            return [1 => 0, 2 => 0, 3 => 0];
        }

        $base = intdiv($this->qty_to_run, $count);
        $rem = $this->qty_to_run % $count;

        $result = [1 => 0, 2 => 0, 3 => 0];
        foreach ($activeShifts as $idx => $shiftNum) {
            $result[$shiftNum] = $base + ($idx < $rem ? 1 : 0);
        }

        return $result;
    }
}
