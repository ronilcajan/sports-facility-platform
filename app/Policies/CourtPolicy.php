<?php

namespace App\Policies;

use App\Models\Court;
use App\Models\User;

class CourtPolicy
{
    /**
     * Determine whether the user can view any courts.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('courts.viewAny');
    }

    /**
     * Determine whether the user can view the court.
     *
     * Staff may only view courts they are assigned to; admins see all.
     */
    public function view(User $user, Court $court): bool
    {
        return $user->hasPermissionTo('courts.view')
            && $user->canManageCourt($court);
    }

    /**
     * Determine whether the user can create courts.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('courts.create');
    }

    /**
     * Determine whether the user can update the court.
     *
     * Scoped to assigned courts for non-admin roles, mirroring view().
     */
    public function update(User $user, Court $court): bool
    {
        return $user->hasPermissionTo('courts.update')
            && $user->canManageCourt($court);
    }

    /**
     * Determine whether the user can delete the court.
     */
    public function delete(User $user, Court $court): bool
    {
        return $user->hasPermissionTo('courts.delete')
            && $user->canManageCourt($court);
    }

    /**
     * Determine whether the user can assign staff to the court.
     */
    public function assignStaff(User $user, Court $court): bool
    {
        return $user->hasPermissionTo('courts.assignStaff')
            && $user->canManageCourt($court);
    }
}
