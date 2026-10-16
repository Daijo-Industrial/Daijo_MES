<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asakai extends Model
{
    use SoftDeletes;

    protected $table = 'asakai_master'; // ← FIX INI

    protected $fillable = [
        'customer', 'part_no', 'issue', 'quantity',
        'lot_shift', 'lot_date', 'date_issue',
        'pokayoke', 'verify', 'fmea_cp', 'std_work',
        'audit_date', 'reply_date', 'status',
        'created_by', 'updated_by', 'closed_at', 'remark',
    ];

    protected $casts = [
        'lot_date' => 'date',
        'date_issue' => 'date',
        'audit_date' => 'date',
        'reply_date' => 'date',
        'closed_at' => 'datetime',
        'quantity' => 'integer',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function pics()
    {
        return $this->hasMany(AsakaiPic::class, 'asakai_id')->orderBy('order_no');
    }

    public function rcas()
    {
        return $this->hasMany(AsakaiRca::class, 'asakai_id')->orderBy('why_level');
    }

    public function correctiveActions()
    {
        return $this->hasMany(AsakaiCorrectiveAction::class, 'asakai_id')->orderBy('order_no');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getPicNamesAttribute()
    {
        return $this->pics->pluck('pic_name')->implode(', ');
    }

    public function getRcaListAttribute()
    {
        return $this->rcas->map(function ($rca) {
            return [
                'level' => $rca->why_level,
                'label' => "Why {$rca->why_level}",
                'description' => $rca->description,
            ];
        });
    }

    public function getCorrectiveActionListAttribute()
    {
        return $this->correctiveActions->pluck('action')->toArray();
    }

    public function getLotDateFormattedAttribute()
    {
        return $this->lot_date->format('d M Y') . ' - ' . $this->lot_shift;
    }

    public function getIsOverdueAttribute()
    {
        if (! $this->reply_date) {
            return false;
        }

        return now()->gt($this->reply_date) && $this->status !== 'closed';
    }

    public function getDaysUntilReplyAttribute()
    {
        if (! $this->reply_date) {
            return;
        }

        return now()->diffInDays($this->reply_date, false);
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeDaily($query, $date)
    {
        return $query->whereDate('date_issue', $date);
    }

    public function scopeWeekly($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_issue', [$startDate, $endDate]);
    }

    public function scopeMonthly($query, $year, $month)
    {
        return $query->whereYear('date_issue', $year)
            ->whereMonth('date_issue', $month);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'closed')
            ->whereNotNull('reply_date')
            ->whereDate('reply_date', '<', now());
    }

    public function scopeByCustomer($query, $customer)
    {
        return $query->where('customer', 'like', "%{$customer}%");
    }

    public function scopeByPartNo($query, $partNo)
    {
        return $query->where('part_no', 'like', "%{$partNo}%");
    }

    public function scopeByShift($query, $shift)
    {
        return $query->where('lot_shift', $shift);
    }

    // ============================================
    // METHODS
    // ============================================

    public function markAsClosed()
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
            'updated_by' => 1,
        ]);
    }

    public function submit()
    {
        $this->update([
            'status' => 'submitted',
            'updated_by' => 1,
        ]);
    }

    public function canBeEditedBy($user)
    {
        return $this->created_by === $user->id || $user->hasRole('admin');
    }

    public function getCompletionPercentageAttribute()
    {
        $total = 0;
        $filled = 0;

        $requiredFields = ['customer', 'part_no', 'issue', 'quantity',
            'lot_shift', 'lot_date', 'date_issue'];

        foreach ($requiredFields as $field) {
            $total++;
            if (! empty($this->$field)) {
                $filled++;
            }
        }

        $total++;
        if ($this->pics->count() > 0) {
            $filled++;
        }

        $total++;
        if ($this->rcas->count() >= 3) {
            $filled++;
        }

        $total++;
        if ($this->correctiveActions->count() > 0) {
            $filled++;
        }

        $optionalFields = ['pokayoke', 'audit_date', 'reply_date',
            'verify', 'fmea_cp', 'std_work'];

        foreach ($optionalFields as $field) {
            $total++;
            if (! empty($this->$field)) {
                $filled++;
            }
        }

        return round(($filled / $total) * 100);
    }
}
