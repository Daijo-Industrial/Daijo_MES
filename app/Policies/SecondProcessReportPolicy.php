<?php

namespace App\Policies;

use App\Models\SecondProcessReport;
use App\Models\User;

class SecondProcessReportPolicy
{
    /**
     * Universal Super-Admin / Admin bypass.
     * Note: 'update' is handled conditionally inside update() to respect draft status.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($ability !== 'update' && ($user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN'))) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, SecondProcessReport $report): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create reports.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the draft report.
     * Only draft reports can be updated.
     * Authorized: Super-Admin, Admin, creator, authorized checker, or authorized leader.
     */
    public function update(User $user, SecondProcessReport $report): bool
    {
        if ($report->status !== 'draft') {
            return false;
        }

        if ($user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN')) {
            return true;
        }

        return empty($report->created_by_name)
            || $report->created_by_name === $user->name
            || $user->canSign('second_process', 'checker')
            || $user->canSign('second_process', 'leader');
    }

    /**
     * Determine whether the user can delete the report.
     * Authorized: Super-Admin, Admin, or Supervisor ('acknowledged').
     */
    public function delete(User $user, SecondProcessReport $report): bool
    {
        return $user->canSign('second_process', 'acknowledged');
    }

    /**
     * Determine whether the user can sign in a specific approval slot.
     */
    public function sign(User $user, SecondProcessReport $report, string $slot): bool
    {
        return $user->canSign('second_process', $slot);
    }

    /**
     * Determine whether the user can reject the report back to draft.
     * Authorized: Leader, PQC, Supervisor ('acknowledged'), or Admins.
     */
    public function reject(User $user, SecondProcessReport $report): bool
    {
        return $user->canSign('second_process', 'leader')
            || $user->canSign('second_process', 'pqc')
            || $user->canSign('second_process', 'acknowledged');
    }
}
