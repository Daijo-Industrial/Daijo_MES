<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceMachine extends Model
{
    use HasFactory;

    protected $table = 'maintenance_machines';

    protected $fillable = [
        'tanggal',           // tanggal pengerjaan
        'mesin',             // id user / mesin
        'jenis_kerusakan',
        'perbaikan',
        'lama_pengerjaan',   // nanti diisi jam:menit
        'pic',
        'status',            // 0 = ongoing, 1 = finished
        'remark',
        'finished_at',       // timestamp selesai
        'tipe',              // Repair / Maintenance
    ];

    protected $casts = [
        'tanggal' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Accessor untuk otomatis menghitung lama pengerjaan jika finished_at tersedia
     */
    public function getLamaPengerjaanAttribute($value)
    {
        if ($this->finished_at) {
            $created = $this->created_at;
            $finished = $this->finished_at;

            $diffInMinutes = $finished->diffInMinutes($created);
            $hours = intdiv($diffInMinutes, 60);
            $minutes = $diffInMinutes % 60;

            return "{$hours} jam {$minutes} menit";
        }

        return $value;
    }

    /**
     * Relasi ke user (mesin / PIC)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'mesin');
    }
}
