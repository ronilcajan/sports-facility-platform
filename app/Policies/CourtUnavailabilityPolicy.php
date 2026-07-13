<?php

namespace App\Policies;

use App\Models\Court;
use App\Models\CourtUnavailability;
use App\Models\User;

class CourtUnavailabilityPolicy
{
    /**
     * Determine whether the user can view/manage schedule blackout entries for the given court.
     */
    public function manage(User $user, Court $court): bool
    {
        return ($user->canManageAllCourts() || $user->checkPermissionTo('schedules.manage'))
            && ($user->canManageAllCourts() || $user->isAssignedToCourt($court));
    }

    /**
     * Determine whether the user can delete the specific unavailability entry.
     */
    public function delete(User $user, CourtUnavailability $unavailability): bool
    {
        return ($user->canManageAllCourts() || $user->checkPermissionTo('schedules.manage'))
            && ($user->canManageAllCourts() || $user->isAssignedToCourt($unavailability->court));
    }
}
