<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory, MassPrunable;

    protected $fillable = [
        'api_name',
        'method',
        'endpoint',
        'request_payload',
        'response_payload',
        'status_code',
        'status',
        'message',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    /**
     * Tentukan kriteria data yang akan otomatis dihapus (lebih tua dari 3 bulan).
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subMonths(3));
    }
}
