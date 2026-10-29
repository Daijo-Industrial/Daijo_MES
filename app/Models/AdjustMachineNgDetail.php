<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdjustMachineNgDetail extends Model
{
    use HasFactory;

    protected $table = 'adjust_machine_ng_details';

    protected $fillable = [
        'adjust_machine_log_id',
        'ng_type_id',
        'ng_quantity',
        'ng_remarks',
    ];

    public function adjustMachineLog()
    {
        return $this->belongsTo(AdjustMachineLog::class, 'adjust_machine_log_id');
    }

    public function ngType()
    {
        return $this->belongsTo(ProductionNgType::class, 'ng_type_id');
    }
}
