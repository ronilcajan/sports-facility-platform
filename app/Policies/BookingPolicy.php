<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view any bookings.
     */
    public function viewAny(User $user): bool
    {
        return $user->canManageAllCourts()
            || $user->checkPermissionTo('bookings.viewAny')
            || $user->hasRole(RoleName::Customer->value);
    }

    /**
     * Determine whether the user can view the specific booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        if ($user->hasRole(RoleName::Customer->value)) {
            return $booking->user_id === $user->id;
        }

        return ($user->canManageAllCourts() || $user->checkPermissionTo('bookings.view'))
            && ($user->canManageAllCourts() || $user->isAssignedToCourt($booking->court));
    }

    /**
     * Determine whether the user can create bookings.
     */
    public function create(User $user): bool
    {
        return $user->canManageAllCourts()
            || $user->checkPermissionTo('bookings.create')
            || $user->hasRole(RoleName::Customer->value);
    }

    /**
     * Determine whether the user can update the booking (e.g. status changes).
     */
    public function update(User $user, Booking $booking): bool
    {
        if ($user->hasRole(RoleName::Customer->value)) {
            return $booking->user_id === $user->id;
        }

        return ($user->canManageAllCourts() || $user->checkPermissionTo('bookings.update'))
            && ($user->canManageAllCourts() || $user->isAssignedToCourt($booking->court));
    }

    /**
     * Determine whether the user can delete the booking.
     */
    public function delete(User $user, Booking $booking): bool
    {
        if ($user->hasRole(RoleName::Customer->value)) {
            return $booking->user_id === $user->id;
        }

        return $user->canManageAllCourts() || $user->checkPermissionTo('bookings.delete');
    }
}
