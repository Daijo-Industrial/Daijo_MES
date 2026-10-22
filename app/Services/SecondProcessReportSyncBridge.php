<?php

namespace App\Services;

use App\Models\SecondProcessHourlyProduction;
use App\Models\SecondProcessManpower;
use App\Models\SecondProcessMaterial;
use App\Models\SecondProcessNgHourlyDetail;
use App\Models\SecondProcessNgRecord;
use App\Models\SecondProcessReport;
use App\Models\SecondProcessTrouble;
use App\Models\SpProductionSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SecondProcessReportSyncBridge
{
    /**
     * Synchronize an approved SpProductionSession into the legacy SecondProcessReport schema.
     */
    public function syncSessionToLegacyReport(SpProductionSession $session): SecondProcessReport
    {
        return DB::transaction(function () use ($session) {
            $session->loadMissing([
                'workOrder',
                'operator',
                'approvedBy',
                'rejectEntries',
                'downtimeEntries',
                'reworkEntries',
                'inputEntries',
                'manpowerEntries',
                'materials',
            ]);

            $wo = $session->workOrder;

            $dateStr = $session->started_at?->setTimezone(config('mes.timezone', 'Asia/Jakarta'))->format('Y-m-d') ?? Carbon::now(config('mes.timezone', 'Asia/Jakarta'))->format('Y-m-d');
            $unitLine = $session->unit_line ?: ($wo->unit_line ?? 'Line 1');
            $shift = $session->shift ?: ($wo->shift ?? '1');
            $partNumber = $wo->part_number ?? '-';

            // Calculate WIP vs Reworkable input
            $inputReworkable = (int) $session->inputEntries->where('source', 'reworkable')->sum('quantity');
            $inputWip = (int) $session->inputEntries->where(function ($entry) {
                return ($entry->source ?? '') !== 'reworkable';
            })->sum('quantity');
            if ($inputWip === 0 && $session->total_input > 0 && $inputReworkable === 0) {
                $inputWip = (int) $session->total_input;
            }
            $repairan = max($inputReworkable, (int) $session->total_rework_recovered);

            $reportData = [
                'sp_production_session_id' => $session->id,
                'date' => $dateStr,
                'unit_line' => $unitLine,
                'shift' => $shift,
                'part_number' => $partNumber,
                'process_prod' => $wo->process_prod ?? 'Second Process',
                'status' => 'Approved',
                'model' => $wo->model ?? '-',
                'part_name' => $wo->part_name ?? '-',
                'customer' => $wo->customer ?? '-',
                'target_per_hour' => (int) ceil(($wo->target_qty ?? 0) / 8),
                'jml_input_wip' => $inputWip,
                'repairan' => $repairan,
                'jumlah_output' => $session->total_good + $session->total_reject,
                'jumlah_ok' => $session->total_good,
                'jumlah_ng' => $session->total_reject,
                'ng_prosentase' => $session->yield > 0 ? round(100 - $session->yield, 2) : 0,
                'jml_ng_lebur' => $session->total_scrap,
                'leader_name' => $session->operator->name ?? null,
                'leader_signed_at' => $session->finished_at,
                'created_by_name' => $session->operator->name ?? null,
                'created_by_signed_at' => $session->finished_at,
                'production_notes' => $session->production_notes ?? $session->remarks,
                'ng_remarks' => $session->ng_remarks,
                'absent_employees' => $session->absent_employees,
                'next_production_schedule' => (! empty($session->next_production_schedule) && is_array($session->next_production_schedule))
                    ? (array_values(array_filter(array_map('trim', $session->next_production_schedule), fn ($v) => ! empty($v))) ?: null)
                    : null,
                'output_destination' => $session->output_destination,
                'acknowledged_by_name' => $session->approvedBy->name ?? null,
                'acknowledged_signed_at' => $session->approved_at,
            ];

            // Strictly 1-to-1: update existing linked report if re-approving, otherwise create new
            if ($session->second_process_report_id) {
                $report = SecondProcessReport::find($session->second_process_report_id);
            } else {
                $report = null;
            }

            if ($report) {
                $report->update($reportData);
            } else {
                $report = SecondProcessReport::create($reportData);
                $session->update(['second_process_report_id' => $report->id]);
            }

            // Sync NG Defect Records & Hourly Details
            $report->ngRecords()->delete();
            $shiftStart = $this->getShiftStart($session);
            $tz = config('mes.timezone', 'Asia/Jakarta');
            $sessionStartedLocal = ($session->started_at ?: $session->created_at ?: Carbon::now($tz))->copy()->setTimezone($tz);

            $groupedRejects = $session->rejectEntries->groupBy('defect_type');
            foreach ($groupedRejects as $defectType => $entries) {
                $totalQty = $entries->sum('quantity');
                $causes = $entries->pluck('cause')->filter()->implode(', ');

                $ngRecord = SecondProcessNgRecord::create([
                    'report_id' => $report->id,
                    'ng_category' => 'Defect',
                    'ng_name' => $defectType,
                    'total_ng' => $totalQty,
                    'remark' => $causes,
                ]);

                // Map defect occurrences to shift hours (1..8)
                $defectHourly = array_fill(1, 8, 0);
                foreach ($entries as $entry) {
                    $entryTime = ($entry->created_at ?: $sessionStartedLocal)->copy()->setTimezone($tz);
                    $diffMinutes = $shiftStart->diffInMinutes($entryTime, false);
                    $hNum = min(8, max(1, (int) floor($diffMinutes / 60) + 1));
                    $defectHourly[$hNum] += (int) $entry->quantity;
                }

                foreach ($defectHourly as $h => $qty) {
                    if ($qty > 0) {
                        SecondProcessNgHourlyDetail::create([
                            'ng_record_id' => $ngRecord->id,
                            'hour_ke' => $h,
                            'qty' => $qty,
                        ]);
                    }
                }
            }

            // Sync Downtime Troubles
            $report->troubles()->delete();
            foreach ($session->downtimeEntries as $dt) {
                $duration = 0;
                if ($dt->start_time && $dt->resume_time) {
                    $start = Carbon::parse($dt->start_time);
                    $resume = Carbon::parse($dt->resume_time);
                    $duration = $start->diffInMinutes($resume);
                }

                SecondProcessTrouble::create([
                    'report_id' => $report->id,
                    'penyebab' => $dt->category ?? 'Downtime',
                    'masalah' => $dt->reason,
                    'penanganan' => $dt->countermeasure,
                    'loss_time_minutes' => $duration,
                    'category' => $dt->category ?? 'Downtime',
                ]);
            }

            // Sync Materials
            $report->materials()->delete();
            foreach ($session->materials as $mat) {
                if (! empty($mat->item_name) && (! empty($mat->lot_number) || ! empty($mat->qty) || ! empty($mat->visco) || ! empty($mat->mixing_ratio))) {
                    SecondProcessMaterial::create([
                        'report_id' => $report->id,
                        'type' => $mat->type,
                        'item_name' => $mat->item_name,
                        'lot_number' => $mat->lot_number,
                        'visco' => $mat->visco,
                        'mixing_ratio' => $mat->mixing_ratio,
                        'qty' => $mat->qty,
                        'uom' => $mat->uom,
                    ]);
                }
            }

            // Sync Hourly Production Breakdown (8-Hour Shift Schedule)
            $report->hourlyProductions()->delete();
            $hourlyProgression = $this->calculateHourlyProgression($session);

            foreach ($hourlyProgression as $row) {
                SecondProcessHourlyProduction::create([
                    'report_id' => $report->id,
                    'hour_ke' => (string) $row['hour'],
                    'ok_qty' => $row['ok'],
                    'ng_qty' => $row['ng'],
                    'acumulasi_qty' => $row['accumulation'],
                ]);
            }

            // Sync Manpower Breakdown
            $report->manpowers()->delete();
            foreach ($session->manpowerEntries as $index => $mp) {
                SecondProcessManpower::create([
                    'report_id' => $report->id,
                    'role' => $mp->role,
                    'no' => $index + 1,
                    'name' => $mp->operator_name,
                ]);
            }

            return $report;
        });
    }

    /**
     * Handle reversion when a supervisor sends a report back for correction.
     */
    /**
     * Get the scheduled start datetime for a session's shift based on config/mes.php.
     */
    public function getShiftStart(SpProductionSession $session): Carbon
    {
        $tz = config('mes.timezone', 'Asia/Jakarta');
        $shifts = config('mes.sp_shifts', config('mes.shifts', []));
        $shiftId = intval(preg_replace('/[^0-9]/', '', (string) $session->shift)) ?: 1;
        $shiftConfig = $shifts[$shiftId] ?? ($shifts[1] ?? ['start' => '07:30', 'end' => '15:30']);

        $shiftStartStr = $shiftConfig['start'] ?? '07:30';
        $shiftEndStr = $shiftConfig['end'] ?? '15:30';

        $sessionStartedLocal = ($session->started_at ?: $session->created_at ?: Carbon::now($tz))->copy()->setTimezone($tz);
        $shiftDate = $sessionStartedLocal->toDateString();

        // If overnight shift (e.g. 23:30 to 07:30) and session started after midnight before shift end
        if ($shiftStartStr > $shiftEndStr && $sessionStartedLocal->format('H:i') < $shiftEndStr) {
            $shiftDate = $sessionStartedLocal->copy()->subDay()->toDateString();
        }

        return Carbon::createFromFormat('Y-m-d H:i', $shiftDate . ' ' . $shiftStartStr, $tz);
    }

    /**
     * Calculate 8-hour production progression anchored to the actual shift schedule in config/mes.php.
     *
     * @return array<int, array{hour: int, time_range: string, ok: int, ng: int, accumulation: int}>
     */
    public function calculateHourlyProgression(SpProductionSession $session): array
    {
        $session->loadMissing(['productionEntries', 'rejectEntries']);

        $tz = config('mes.timezone', 'Asia/Jakarta');
        $shiftStart = $this->getShiftStart($session);
        $sessionStartedLocal = ($session->started_at ?: $session->created_at ?: Carbon::now($tz))->copy()->setTimezone($tz);

        // Initialize 8 shift hours with scheduled time ranges
        $hourlyData = [];
        for ($h = 1; $h <= 8; $h++) {
            $slotStart = $shiftStart->copy()->addHours($h - 1);
            $slotEnd = $shiftStart->copy()->addHours($h);
            $hourlyData[$h] = [
                'hour' => $h,
                'time_range' => $slotStart->format('H:i') . ' - ' . $slotEnd->format('H:i'),
                'ok' => 0,
                'ng' => 0,
                'accumulation' => 0,
            ];
        }

        foreach ($session->productionEntries as $entry) {
            $entryTime = ($entry->recorded_at ?: $entry->created_at ?: $sessionStartedLocal)->copy()->setTimezone($tz);
            $diffMinutes = $shiftStart->diffInMinutes($entryTime, false);
            $hourNum = min(8, max(1, (int) floor($diffMinutes / 60) + 1));
            $hourlyData[$hourNum]['ok'] += (int) $entry->good_qty;
        }

        foreach ($session->rejectEntries as $reject) {
            $rejectTime = ($reject->created_at ?: $sessionStartedLocal)->copy()->setTimezone($tz);
            $diffMinutes = $shiftStart->diffInMinutes($rejectTime, false);
            $hourNum = min(8, max(1, (int) floor($diffMinutes / 60) + 1));
            $hourlyData[$hourNum]['ng'] += (int) $reject->quantity;
        }

        $runningAccumulation = 0;
        for ($h = 1; $h <= 8; $h++) {
            $runningAccumulation += $hourlyData[$h]['ok'];
            $hourlyData[$h]['accumulation'] = $runningAccumulation;
        }

        return array_values($hourlyData);
    }

    /**
     * Handle reversion when a supervisor sends a report back for correction.
     */
    public function handleSessionReversion(SpProductionSession $session): void
    {
        if ($session->second_process_report_id) {
            $report = SecondProcessReport::find($session->second_process_report_id);
            if ($report) {
                $report->update(['status' => 'Draft']);
            }
        }
    }
}
