<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterMachine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'master_machines';

    protected $fillable = [
        'name',
        'plant',
        'tonnage',
        'brand',
        'line_or_zone',
        'is_active',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope a query to only include active (visible in dashboard) machines.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by plant.
     */
    public function scopePlant($query, ?string $plant)
    {
        if (empty($plant) || strtoupper($plant) === 'ALL') {
            return $query;
        }

        return $query->where('plant', strtoupper($plant));
    }

    /**
     * Relate to machine user account in users table if exists.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'name', 'name');
    }
}
