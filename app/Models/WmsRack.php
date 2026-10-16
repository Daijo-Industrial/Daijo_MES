<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WmsRack extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['whse_id', 'rack_code', 'x_pos', 'y_pos', 'width', 'height', 'orientation', 'zone', 'sort_order'];

    public function warehouse()
    {
        return $this->belongsTo(WmsWarehouse::class, 'whse_id');
    }

    public function positions()
    {
        return $this->hasMany(WmsPosition::class, 'rack_id');
    }

    public function getEffectiveZoneAttribute(): string
    {
        $rawZone = strtoupper(trim($this->attributes['zone'] ?? ''));
        if (! empty($rawZone) && $rawZone !== 'DEFAULT') {
            return $rawZone;
        }

        // Automatic inference if zone not explicitly set
        $code = strtoupper(trim($this->rack_code));
        if (str_starts_with($code, 'F')) {
            return 'LOGISTIC';
        }
        if (str_starts_with($code, 'W')) {
            return 'STORE';
        }
        if (str_starts_with($code, 'R')) {
            return 'WIP';
        }

        return 'DEFAULT';
    }

    public function getZoneColorClassesAttribute(): array
    {
        return match ($this->effective_zone) {
            'LOGISTIC' => [
                'bg' => 'bg-emerald-50/80',
                'border' => 'border-emerald-300',
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'text' => 'text-emerald-800',
                'header_bg' => 'bg-emerald-600 text-white',
                'label' => '🟢 LOGISTIC',
            ],
            'STORE' => [
                'bg' => 'bg-blue-50/80',
                'border' => 'border-blue-300',
                'badge' => 'bg-blue-100 text-blue-800 border-blue-300',
                'text' => 'text-blue-800',
                'header_bg' => 'bg-blue-600 text-white',
                'label' => '🔵 STORE',
            ],
            'WIP' => [
                'bg' => 'bg-amber-50/80',
                'border' => 'border-amber-300',
                'badge' => 'bg-amber-100 text-amber-800 border-amber-300',
                'text' => 'text-amber-800',
                'header_bg' => 'bg-amber-600 text-white',
                'label' => '🟠 WIP',
            ],
            'FG' => [
                'bg' => 'bg-purple-50/80',
                'border' => 'border-purple-300',
                'badge' => 'bg-purple-100 text-purple-800 border-purple-300',
                'text' => 'text-purple-800',
                'header_bg' => 'bg-purple-600 text-white',
                'label' => '🟣 FG',
            ],
            default => [
                'bg' => 'bg-slate-50/80',
                'border' => 'border-slate-300',
                'badge' => 'bg-slate-100 text-slate-800 border-slate-300',
                'text' => 'text-slate-800',
                'header_bg' => 'bg-slate-700 text-white',
                'label' => '⚪ DEFAULT',
            ],
        };
    }

    public function getDistanceScoreAttribute(): int
    {
        $whseCode = strtoupper($this->warehouse?->whse_code ?? '');
        $exitLoc = $this->warehouse?->exit_location;

        if ($exitLoc === 'BOTTOM_LEFT' || str_contains($whseCode, 'GUA')) {
            $exitX = 180;
            $exitY = 730;
        } elseif ($exitLoc === 'BOTTOM_CENTER' || str_contains($whseCode, '06') || str_contains($whseCode, 'J06')) {
            $exitX = 500;
            $exitY = 730;
        } elseif ($exitLoc === 'TOP_LEFT') {
            $exitX = 180;
            $exitY = 100;
        } elseif ($exitLoc === 'TOP_RIGHT') {
            $exitX = 880;
            $exitY = 100;
        } elseif ($exitLoc === 'TOP_CENTER') {
            $exitX = 500;
            $exitY = 100;
        } else {
            // Default: BOTTOM_RIGHT (e.g. GUB)
            $exitX = 880;
            $exitY = 730;
        }

        return abs(($this->x_pos ?: 500) - $exitX) + abs(($this->y_pos ?: 500) - $exitY);
    }
}
