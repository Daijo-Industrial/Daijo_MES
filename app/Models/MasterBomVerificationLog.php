<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBomVerificationLog extends Model
{
    use HasFactory;

    protected $table = 'master_bom_verification_logs';

    protected $fillable = [
        'parent_item',
        'verified_at',
        'verified_by',
        'verified_by_name',
        'notes',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
