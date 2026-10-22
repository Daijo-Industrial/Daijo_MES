<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HourlyRemark extends Model
{
    use HasFactory;

    protected $fillable = [
        'dic_id', // DailyItemCode ID
        'start_time',
        'end_time',
        'target',
        'actual',
        'remark',
        'is_achieve',
        'pic',
        'pic_2',
        'pic_3',
        'actual_production',
        'NG',
    ];

    public function getPicsArrayAttribute()
    {
        return array_values(array_filter([$this->pic, $this->pic_2, $this->pic_3]));
    }

    public function getPicsStringAttribute()
    {
        return implode(', ', $this->pics_array);
    }

    public function dailyItemCode()
    {
        return $this->belongsTo(DailyItemCode::class, 'dic_id');
    }

    public function ngDetails()
    {
        return $this->hasMany(ProductionNgDetail::class, 'hourly_remark_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            // Jika kolom actual_production tidak berubah,
            // nonaktifkan timestamps agar updated_at tidak diperbarui
            if (! $model->isDirty('actual_production')) {
                $model->timestamps = false;
            }
        });
    }
}
