<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsakaiPic extends Model
{
    protected $table = 'asakai_pic';

    protected $fillable = [
        'asakai_id',
        'pic_name',
        'order_no',
    ];

    public function asakai()
    {
        return $this->belongsTo(Asakai::class, 'asakai_id'); // Laravel otomatis cari di asakai_master
    }
}
