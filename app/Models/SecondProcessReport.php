<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SecondProcessReport extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'process_prod',
                'unit_line',
                'shift',
                'part_number',
                'customer',
                'jumlah_ok',
                'jumlah_ng',
                'created_by_name',
                'leader_name',
                'leader_signed_at',
                'pqc_name',
                'pqc_signed_at',
                'acknowledged_by_name',
                'acknowledged_signed_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('second_process');
    }

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

    /**
     * Finds a subsequent report (within 7 days) that absorbs / covers this report's sisa_input.
     */
    public function findCoveringReport(): ?SecondProcessReport
    {
        if ((int) $this->sisa_input <= 0) {
            return null;
        }

        $sisa = (int) $this->sisa_input;
        $startDate = Carbon::parse($this->date);
        $endDate = $startDate->copy()->addDays(7)->format('Y-m-d');

        return self::where('part_number', $this->part_number)
            ->where('unit_line', $this->unit_line)
            ->where('process_prod', $this->process_prod)
            ->where('id', '!=', $this->id)
            ->whereBetween('date', [$this->date, $endDate])
            ->where(function ($q) {
                $q->where('date', '>', $this->date)
                    ->orWhere(function ($sameDateQ) {
                        $sameDateQ->where('date', $this->date)
                            ->where(function ($shiftQ) {
                                $shiftQ->where('shift', '>', $this->shift)
                                    ->orWhere(function ($idQ) {
                                        $idQ->where('shift', $this->shift)
                                            ->where('id', '>', $this->id);
                                    });
                            });
                    });
            })
            ->where(function ($matchQ) use ($sisa) {
                $matchQ->where('jumlah_output', $sisa)
                    ->orWhere('jml_input_wip', $sisa)
                    ->orWhere('repairan', $sisa)
                    ->orWhereRaw('(COALESCE(jml_input_wip, 0) + COALESCE(repairan, 0)) = ?', [$sisa]);
            })
            ->orderBy('date', 'asc')
            ->orderBy('shift', 'asc')
            ->orderBy('id', 'asc')
            ->first();
    }

    /**
     * Finds a preceding report (within 7 days) whose sisa_input was absorbed / covered by this report.
     */
    public function findPrecedingSisaReport(): ?SecondProcessReport
    {
        $inputTotal = (int) ($this->jml_input_wip ?? 0) + (int) ($this->repairan ?? 0);
        $outputTotal = (int) ($this->jumlah_output ?? 0);

        if ($inputTotal <= 0 && $outputTotal <= 0) {
            return null;
        }

        $startDate = Carbon::parse($this->date)->subDays(7)->format('Y-m-d');

        return self::where('part_number', $this->part_number)
            ->where('unit_line', $this->unit_line)
            ->where('process_prod', $this->process_prod)
            ->where('id', '!=', $this->id)
            ->where('sisa_input', '>', 0)
            ->whereBetween('date', [$startDate, $this->date])
            ->where(function ($q) {
                $q->where('date', '<', $this->date)
                    ->orWhere(function ($sameDateQ) {
                        $sameDateQ->where('date', $this->date)
                            ->where(function ($shiftQ) {
                                $shiftQ->where('shift', '<', $this->shift)
                                    ->orWhere(function ($idQ) {
                                        $idQ->where('shift', $this->shift)
                                            ->where('id', '<', $this->id);
                                    });
                            });
                    });
            })
            ->where(function ($matchQ) use ($inputTotal, $outputTotal) {
                $matchQ->where('sisa_input', $inputTotal)
                    ->orWhere('sisa_input', $outputTotal)
                    ->orWhere('sisa_input', (int) $this->jml_input_wip)
                    ->orWhere('sisa_input', (int) $this->repairan);
            })
            ->orderBy('date', 'desc')
            ->orderBy('shift', 'desc')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Structured reconciliation status badge and link
     */
    public function getReconciliationStatusAttribute(): array
    {
        if ((int) $this->sisa_input > 0) {
            $covering = $this->findCoveringReport();
            if ($covering) {
                return [
                    'type' => 'reconciled',
                    'badge' => '✓ Sisa Tertutup',
                    'message' => "Sisa {$this->sisa_input} pcs diproses di Shift {$covering->shift} ({$covering->date}) - Laporan #{$covering->id}",
                    'linked_report_id' => $covering->id,
                ];
            }

            return [
                'type' => 'open',
                'badge' => '⚠️ Sisa Belum Klir',
                'message' => "Sisa {$this->sisa_input} pcs belum ada laporan penutup.",
                'linked_report_id' => null,
            ];
        }

        $preceding = $this->findPrecedingSisaReport();
        if ($preceding) {
            return [
                'type' => 'absorber',
                'badge' => '↪️ Penutup Sisa',
                'message' => "Menyerap sisa {$preceding->sisa_input} pcs dari Shift {$preceding->shift} ({$preceding->date}) - Laporan #{$preceding->id}",
                'linked_report_id' => $preceding->id,
            ];
        }

        return [
            'type' => 'none',
            'badge' => null,
            'message' => null,
            'linked_report_id' => null,
        ];
    }

    /**
     * Scope query to filter reports by sisa_input state (has_sisa, open, reconciled, deficit, ideal).
     */
    public function scopeApplySisaFilter($query, ?string $filter)
    {
        if (empty($filter) || $filter === 'all') {
            return $query;
        }

        if ($filter === 'has_sisa') {
            return $query->where('sisa_input', '>', 0);
        }

        if ($filter === 'deficit') {
            return $query->where('sisa_input', '<', 0);
        }

        if ($filter === 'ideal') {
            return $query->where('sisa_input', 0);
        }

        if ($filter === 'open' || $filter === 'reconciled') {
            $isReconciled = $filter === 'reconciled';
            $method = $isReconciled ? 'whereExists' : 'whereNotExists';

            return $query->where('sisa_input', '>', 0)
                ->{$method}(function ($subQ) {
                    $driver = DB::getDriverName();
                    $dateLimitClause = $driver === 'sqlite'
                        ? "covering.date <= date(second_process_reports.date, '+7 days')"
                        : 'covering.date <= DATE_ADD(second_process_reports.date, INTERVAL 7 DAY)';

                    $subQ->select(DB::raw(1))
                        ->from('second_process_reports as covering')
                        ->whereColumn('covering.part_number', 'second_process_reports.part_number')
                        ->whereColumn('covering.unit_line', 'second_process_reports.unit_line')
                        ->whereColumn('covering.process_prod', 'second_process_reports.process_prod')
                        ->whereColumn('covering.id', '!=', 'second_process_reports.id')
                        ->whereRaw('covering.date >= second_process_reports.date')
                        ->whereRaw($dateLimitClause)
                        ->where(function ($orderQ) {
                            $orderQ->whereColumn('covering.date', '>', 'second_process_reports.date')
                                ->orWhere(function ($sameDateQ) {
                                    $sameDateQ->whereColumn('covering.date', 'second_process_reports.date')
                                        ->where(function ($shiftQ) {
                                            $shiftQ->whereColumn('covering.shift', '>', 'second_process_reports.shift')
                                                ->orWhere(function ($idQ) {
                                                    $idQ->whereColumn('covering.shift', 'second_process_reports.shift')
                                                        ->whereColumn('covering.id', '>', 'second_process_reports.id');
                                                });
                                        });
                                });
                        })
                        ->where(function ($matchQ) {
                            $matchQ->whereColumn('covering.jumlah_output', 'second_process_reports.sisa_input')
                                ->orWhereColumn('covering.jml_input_wip', 'second_process_reports.sisa_input')
                                ->orWhereColumn('covering.repairan', 'second_process_reports.sisa_input')
                                ->orWhereRaw('(COALESCE(covering.jml_input_wip, 0) + COALESCE(covering.repairan, 0)) = second_process_reports.sisa_input');
                        });
                });
        }

        return $query;
    }
}
