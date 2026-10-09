<?php

namespace App\Policies;

use App\Models\FirstPieceInspection;
use App\Models\SecondProcessReport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

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
    public function update(User $user, SecondProcessReport $report): Response
    {
        if ($report->status !== 'draft') {
            return Response::deny('Only draft reports can be edited.');
        }

        if ($user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN')) {
            return Response::allow();
        }

        $isAuthorized = empty($report->created_by_name)
            || $report->created_by_name === $user->name
            || $user->canSign('second_process', 'checker')
            || $user->canSign('second_process', 'leader');

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
