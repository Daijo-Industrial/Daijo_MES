<?php

namespace App\Policies;

use App\Models\IpqcInspection;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IpqcInspectionPolicy
{
    /**
     * Determine whether the user can view any IPQC inspections.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('second-process-ipqc');
    }

    /**
     * Determine whether the user can view the inspection.
     */
    public function view(User $user, IpqcInspection $inspection): bool
    {
        return $user->can('second-process-ipqc');
    }

    /**
     * Determine whether the user can create inspections.
     */
    public function create(User $user): bool
    {
        return $user->can('second-process-ipqc');
    }

    /**
     * Determine whether the user can update the inspection.
     */
    public function update(User $user, IpqcInspection $inspection): Response
    {
        if (! $user->can('second-process-ipqc')) {
            return Response::deny('You do not have permission to edit IPQC Inspections.');
        }

        if ($inspection->status !== 'ongoing') {
            return Response::deny('Cannot update a completed inspection.');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can delete the inspection.
     */
    public function delete(User $user, IpqcInspection $inspection): Response
    {
        if (! $user->can('second-process-ipqc')) {
            return Response::deny('You do not have permission to delete IPQC Inspections.');
        }

        if ($inspection->status !== 'ongoing' && ! ($user->hasRole('SUPER-ADMIN') || $user->hasRole('ADMIN'))) {
            return Response::deny('Completed IPQC inspections cannot be deleted.');
        }

        return Response::allow();
    }
}

