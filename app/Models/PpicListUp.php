<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PpicListUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'zone',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(PpicListUpItem::class, 'list_up_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
