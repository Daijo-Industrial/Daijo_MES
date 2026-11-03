<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterItemLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_code',
        'action',
        'upload_batch_id',
        'file_name',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batch()
    {
        return $this->belongsTo(MasterItemImportBatch::class, 'upload_batch_id', 'batch_code');
    }
}
