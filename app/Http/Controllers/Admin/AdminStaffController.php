<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AdminStaffController extends Controller
{
    /**
     * Display a list of all staff members and their court assignments.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Court::class);

        $staffMembers = User::role(RoleName::Staff->value)
            ->with('assignedCourts')
            ->latest('id')
            ->get();

        $courts = Court::select(['id', 'name', 'sport_type'])->get();

        return Inertia::render('admin/staff/Index', [
            'staffMembers' => $staffMembers,
            'courts' => $courts,
        ]);
    }

    /**
     * Create a new staff account and assign court(s).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Court::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', Password::defaults()],
            'court_ids' => ['nullable', 'array'],
            'court_ids.*' => [Rule::exists('courts', 'id')],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $user->assignRole(RoleName::Staff->value);

        if (! empty($validated['court_ids'])) {
            $user->assignedCourts()->sync($validated['court_ids']);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Staff member created successfully.')]);

        return back();
    }

    /**
     * Update existing staff court assignments or profile.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', Court::class);

        if (! $user->hasRole(RoleName::Staff->value)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'court_ids' => ['nullable', 'array'],
            'court_ids.*' => [Rule::exists('courts', 'id')],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => ! empty($validated['password']) ? bcrypt($validated['password']) : $user->password,
        ]);

        $user->assignedCourts()->sync($validated['court_ids'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Staff member updated successfully.')]);

        return back();
    }

    /**
     * Delete a staff user account.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', Court::class);

        if (! $user->hasRole(RoleName::Staff->value)) {
            abort(403);
        }

        $user->assignedCourts()->detach();
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Staff account deleted.')]);

        return back();
    }
}
