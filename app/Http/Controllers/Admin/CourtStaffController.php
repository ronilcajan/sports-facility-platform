<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CourtStaffController extends Controller
{
    /**
     * Assign a staff member to the court.
     */
    public function store(Request $request, Court $court): RedirectResponse
    {
        $this->authorize('assignStaff', $court);

        $validated = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn(
                        'id',
                        User::role(RoleName::Staff->value)->select('id'),
                    ),
                ),
            ],
        ]);

        $court->staff()->syncWithoutDetaching([$validated['user_id']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Staff assigned.')]);

        return back();
    }

    /**
     * Remove a staff member from the court.
     */
    public function destroy(Court $court, User $user): RedirectResponse
    {
        $this->authorize('assignStaff', $court);

        $court->staff()->detach($user->getKey());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Staff unassigned.')]);

        return back();
    }
}
