<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecondProcessReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'unit_line',
        'shift',
        'process_prod',
        'status',
        'output_destination',
        'model',
        'part_number',
        'part_name',
        'customer',
        'target_per_hour',
        'jml_input_wip',
        'repairan',
        'jumlah_output',
        'sisa_input',
        'jumlah_ok',
        'jumlah_ng',
        'ng_prosentase',
        'jml_ng_lebur',
        'next_production_schedule',
        'absent_employees',
        'production_notes',
        'ng_remarks',
        'sisa_input_remark',
        'created_by_name',
        'created_by_signed_at',
        'pqc_name',
        'pqc_signed_at',
        'leader_name',
        'leader_signed_at',
        'acknowledged_by_name',
        'acknowledged_signed_at',

        // IPQC Header & Summary fields
        'ipqc_lot_color',
        'ipqc_std_glossy',
        'ipqc_std_viscosity',
        'ipqc_std_oven_temp',
        'ipqc_product_color',
        'ipqc_app_sample',
        'ipqc_selected_measurements',
        'ipqc_total_output',
        'ipqc_total_sample',
        'ipqc_total_reject_sample',
        'ipqc_total_reject_rate',
        'ipqc_total_pass',
        'ipqc_total_reject',
        'ipqc_inspector_name',
        'ipqc_checker_name',
        'ipqc_overall_judgement',
        'sp_production_session_id',
    ];

    protected $casts = [
        'next_production_schedule' => 'array',
        'ipqc_selected_measurements' => 'array',
    ];

    public function spProductionSession()
    {
        return $this->belongsTo(SpProductionSession::class, 'sp_production_session_id');
    }

    public function materials()
    {
        return $this->hasMany(SecondProcessMaterial::class, 'report_id');
    }

    public function hourlyProductions()
    {
        return $this->hasMany(SecondProcessHourlyProduction::class, 'report_id');
    }

    public function manpowers()
    {
        return $this->hasMany(SecondProcessManpower::class, 'report_id');
    }

    public function ngRecords()
    {
        return $this->hasMany(SecondProcessNgRecord::class, 'report_id');
    }

    public function troubles()
    {
        return $this->hasMany(SecondProcessTrouble::class, 'report_id');
    }

    public function ipqcRecords()
    {
        return $this->hasMany(SecondProcessIpqcRecord::class, 'report_id');
    }

    public function qcAttachments()
    {
        return $this->morphMany(QcAttachment::class, 'attachable');
    }

    /**
     * Check if a given user is authorized to sign for a specific signature role/slot in Second Process.
     */
    public static function isUserAuthorizedToSign(?User $user, string $role): bool
    {
        if (! $user) {
            return false;
        }

        return $user->canSign('second_process', $role);
    }
}
