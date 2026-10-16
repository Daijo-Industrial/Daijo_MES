<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionNgDetail extends Model
{
    use HasFactory;

    protected $table = 'production_ng_details';

    protected $fillable = [
        'hourly_remark_id',
        'ng_type_id',
        'ng_quantity',
        'ng_remarks',
    ];

    /** Relasi ke Hourly Remark */
    public function hourlyRemark()
    {
        return $this->belongsTo(HourlyRemark::class, 'hourly_remark_id');
    }

    public function ngType()
    {
        return $this->belongsTo(ProductionNgType::class, 'ng_type_id');
    }
}
