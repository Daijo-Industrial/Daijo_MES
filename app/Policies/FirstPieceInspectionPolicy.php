<?php

namespace App\Policies;

use App\Models\FirstPieceInspection;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FirstPieceInspectionPolicy
{
    /**
     * Determine whether the user can view any first piece inspections.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('second-process-first-piece');
    }

    /**
     * Determine whether the user can view the inspection.
     */
    public function view(User $user, FirstPieceInspection $inspection): bool
    {
        return $user->can('second-process-first-piece');
    }

    /**
     * Determine whether the user can create inspections.
     */
    public function create(User $user): bool
    {
        return $user->can('second-process-first-piece');
    }

    /**
     * Determine whether the user can update the inspection.
     */
    public function update(User $user, FirstPieceInspection $inspection): Response
    {
        if (! $user->can('second-process-first-piece')) {
            return Response::deny('You do not have permission to edit First Piece Inspections.');
        }

        if ($inspection->checked_at !== null) {
            return Response::deny('Inspection already checked by QC cannot be edited.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the inspection.
     */
    public function delete(User $user, FirstPieceInspection $inspection): Response
    {
        if (! $user->can('second-process-first-piece')) {
            return Response::deny('You do not have permission to delete First Piece Inspections.');
        }

        if ($inspection->checked_at !== null && ! ($user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN'))) {
            return Response::deny('Inspection checked by QC cannot be deleted.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can sign in a specific approval slot.
     */
    public function sign(User $user, FirstPieceInspection $inspection, string $slot): Response
    {
        if (! $user->can('second-process-first-piece')) {
            return Response::deny('You do not have permission to sign First Piece Inspections.');
        }

        $isAdmin = $user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN');
        if (! $isAdmin && ! $user->canSign('first_piece', $slot)) {
            $userRole = $user->role?->name ?? 'No Role';
            return Response::deny("Role '{$userRole}' is not authorized to sign as " . ucfirst($slot) . '.');
        }

        if ($slot === 'approved' && empty($inspection->checked_at)) {
            return Response::deny('QC Leader approval requires prior QC Inspector check.');
        }

        return Response::allow();
    }
}

