<?php

namespace App\Policies;

use App\Models\FirstPieceInspection;
use App\Models\SecondProcessReport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SecondProcessReportPolicy
{
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
     * Determine whether the user can update the report.
     * - Super-Admin & Admin: Can edit reports in any status.
     * - Leader: Can edit reports prior to final Supervisor acknowledgment (draft, submitted, leader_approved, pqc_approved).
     * - Creator / Checker: Can edit only draft reports.
     * - Acknowledged reports: Locked and cannot be edited by Leader or Checker/Operator.
     */
    public function update(User $user, SecondProcessReport $report): Response
    {
        $isAdmin = $user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN');
        if ($isAdmin) {
            return Response::allow();
        }

        if ($report->status === 'acknowledged') {
            return Response::deny('Laporan yang telah di-Acknowledge tidak dapat diubah.');
        }

        $isLeader = $user->canSign('second_process', 'leader') || $user->hasRole('LEADER');
        if ($isLeader) {
            return Response::allow();
        }

        if ($report->status !== 'draft') {
            return Response::deny('Laporan yang sudah disubmit hanya dapat diedit oleh Leader atau Admin.');
        }

        $isAuthorized = empty($report->created_by_name)
            || $report->created_by_name === $user->name
            || $user->canSign('second_process', 'checker');

        return $isAuthorized
            ? Response::allow()
            : Response::deny('You do not have permission to edit this draft report.');
    }

    /**
     * Determine whether the user can delete the report.
     * Authorized: Super-Admin, Admin, or Supervisor ('acknowledged').
     */
    public function delete(User $user, SecondProcessReport $report): Response
    {
        if ($user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN') || $user->canSign('second_process', 'acknowledged')) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to delete this report.');
    }

    /**
     * Determine whether the user can sign in a specific approval slot.
     * Evaluates role authorization, sequential workflow state, and First Piece Inspection gate.
     */
    public function sign(User $user, SecondProcessReport $report, string $slot): Response
    {
        $isAdmin = $user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN');

        if (! $isAdmin && ! $user->canSign('second_process', $slot)) {
            $userRoleName = $user->role ? $user->role->name : 'No Role';

            return Response::deny("Role '{$userRoleName}' is not authorized to sign as " . ucfirst($slot) . '.');
        }

        switch ($slot) {
            case 'checker':
                if ($report->status !== 'draft') {
                    return Response::deny('Checker signature can only be applied to draft reports.');
                }
                break;

            case 'leader':
                if (! in_array($report->status, ['submitted', 'pqc_approved'])) {
                    return Response::deny('Leader signature can only be applied to submitted reports.');
                }
                break;

            case 'pqc':
                if (! in_array($report->status, ['leader_approved', 'acknowledged', 'submitted'])) {
                    return Response::deny('PQC signature can only be applied after Leader approval.');
                }

                // Check First Piece Approval Gate
                $firstPiece = FirstPieceInspection::where('part_number', $report->part_number)
                    ->whereDate('date', $report->date)
                    ->orderBy('id', 'desc')
                    ->first();

                if (! $firstPiece || ! $firstPiece->isApproved()) {
                    return Response::deny("Cannot sign PQC approval: First Piece Inspection for part '{$report->part_number}' on {$report->date} is not approved by QC.");
                }
                break;

            case 'acknowledged':
                if (! in_array($report->status, ['leader_approved', 'pqc_approved'])) {
                    return Response::deny('Supervisor signature can only be applied after Leader approval.');
                }
                break;

            default:
                return Response::deny('Invalid approval role.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can reject the report back to draft.
     * Authorized: Leader, PQC, Supervisor ('acknowledged'), or Admins when report is submitted or under review.
     */
    public function reject(User $user, SecondProcessReport $report): Response
    {
        if (! in_array($report->status, ['submitted', 'leader_approved', 'pqc_approved'])) {
            return Response::deny('Only submitted or in-review reports can be rejected.');
        }

        $canReject = $user->hasRole('SUPER-ADMIN')
            || $user->hasRole('ADMIN')
            || $user->canSign('second_process', 'leader')
            || $user->canSign('second_process', 'pqc')
            || $user->canSign('second_process', 'acknowledged');

        return $canReject
            ? Response::allow()
            : Response::deny('You are not authorized to reject reports.');
    }
}
