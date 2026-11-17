<?php

namespace App\Livewire\Inspection\Concerns;

use App\Models\Inspection\InspectionReport;
use Carbon\Carbon;

trait HandlesInspectionDraft
{
    public function findPendingDraft(): ?array
    {
        $sessionHeader = session('stepHeaderSaved');
        $sessionDetail = session('stepDetailSaved');
        $user = auth()->user();

        // 1. Check if active session holds draft data
        if (! empty($sessionHeader['document_number'])) {
            $docNum = $sessionHeader['document_number'];
            $dbReport = InspectionReport::where('document_number', $docNum)->first();

            // If already submitted in database, this session is stale - clear it!
            if ($dbReport && $dbReport->status === 'submitted') {
                $this->clearWizardSession();
            } else {
                $lastStep = (int) session('lastStepVisited', 0);
                if ($lastStep < 1 || $lastStep > 4) {
                    $lastStep = ! empty($sessionDetail['details']) ? 2 : 1;
                }

                return [
                    'id' => $dbReport?->id,
                    'document_number' => $docNum,
                    'customer' => $sessionHeader['customer'] ?? ($dbReport?->customer ?? '-'),
                    'part_number' => $sessionHeader['part_number'] ?? ($dbReport?->part_number ?? '-'),
                    'part_name' => $sessionHeader['part_name'] ?? ($dbReport?->part_name ?? '-'),
                    'machine_number' => $sessionHeader['machine_number'] ?? ($dbReport?->machine_number ?? '-'),
                    'shift' => $sessionHeader['shift'] ?? ($dbReport?->shift ?? '-'),
                    'inspection_date' => $sessionHeader['inspection_date'] ?? ($dbReport?->inspection_date?->format('Y-m-d') ?? '-'),
                    'inspector' => $sessionHeader['inspector'] ?? ($dbReport?->inspector ?? '-'),
                    'updated_at' => session('stepHeaderSavedAt')
                        ? Carbon::parse(session('stepHeaderSavedAt'))->diffForHumans()
                        : ($dbReport?->updated_at?->diffForHumans() ?? 'Baru saja'),
                    'source' => $dbReport ? 'db' : 'session',
                    'last_step' => $lastStep,
                ];
            }
        }

        // 2. Check if the authenticated inspector has an unfinished draft in the database
        if ($user && ! empty($user->name)) {
            $userDraft = InspectionReport::where('status', 'draft')
                ->where('inspector', $user->name)
                ->latest('updated_at')
                ->first();

            if ($userDraft) {
                return [
                    'id' => $userDraft->id,
                    'document_number' => $userDraft->document_number,
                    'customer' => $userDraft->customer ?? '-',
                    'part_number' => $userDraft->part_number ?? '-',
                    'part_name' => $userDraft->part_name ?? '-',
                    'machine_number' => $userDraft->machine_number ?? '-',
                    'shift' => $userDraft->shift ?? '-',
                    'inspection_date' => $userDraft->inspection_date?->format('Y-m-d') ?: (string) $userDraft->inspection_date,
                    'inspector' => $userDraft->inspector ?? '-',
                    'updated_at' => $userDraft->updated_at?->diffForHumans() ?? 'Baru saja',
                    'source' => 'db',
                    'last_step' => 2,
                ];
            }
        }

        return null;
    }

    public function clearWizardSession(): void
    {
        session()->forget([
            'stepHeaderSaved',
            'stepHeaderSavedAt',
            'stepHeaderSavedHash',
            'stepHeaderSaved_default_snp',
            'stepDetailSaved',
            'stepProblemSaved',
            'stepProblemSaved_meta',
            'stepFinalSubmit',
            'lastStepVisited',
        ]);
    }
}
