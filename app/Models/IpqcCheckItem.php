<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpqcCheckItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    public function scopeAppearance($query)
    {
        return $query->where('category', 'appearance');
    }

    public function scopeCondition($query)
    {
        return $query->where('category', 'condition');
    }
}
