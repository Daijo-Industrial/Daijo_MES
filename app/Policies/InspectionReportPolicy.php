<?php

namespace App\Policies;

use App\Models\Inspection\InspectionReport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InspectionReportPolicy
{
    /**
     * Determine whether the user can view any inspection reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-quality-links') || $user->can('execute-qc-inspections');
    }

    /**
     * Determine whether the user can view the inspection report.
     */
    public function view(User $user, InspectionReport $report): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create inspection reports.
     */
    public function create(User $user): bool
    {
        return $user->can('execute-qc-inspections');
    }

    /**
     * Determine whether the user can update the inspection report.
     */
    public function update(User $user, InspectionReport $report): Response
    {
        if (! $user->can('execute-qc-inspections')) {
            return Response::deny('Anda tidak memiliki izin untuk mengedit laporan inspeksi.');
        }

        if ($report->status !== 'draft' && ! ($user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN'))) {
            return Response::deny('Laporan inspeksi yang sudah disubmit tidak dapat diedit kembali.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the inspection report.
     */
    public function delete(User $user, InspectionReport $report): Response
    {
        if (! $user->can('execute-qc-inspections')) {
            return Response::deny('Anda tidak memiliki izin untuk menghapus laporan inspeksi.');
        }

        if ($report->status !== 'draft') {
            return Response::deny('Hanya laporan inspeksi berstatus draft yang dapat dihapus.');
        }

        return Response::allow();
    }
}
