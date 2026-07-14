<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    /**
     * Display list of registered users/customers with search.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::withCount('assignedCourts')
            ->latest();

        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->through(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'created_at' => $user->created_at?->toFormattedDateString(),
            ];
        })->withQueryString();

        /** @var User $currentUser */
        $currentUser = $request->user();

        // Build available role options based on current user's role
        $roleOptions = collect(RoleName::cases())
            ->filter(function (RoleName $role) use ($currentUser): bool {
                // Super admin can create any role
                if ($currentUser->isSuperAdmin()) {
                    return true;
                }
                // Admin can only create staff and customer
                return in_array($role, [RoleName::Staff, RoleName::Customer]);
            })
            ->values();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'roles' => $roleOptions,
            'canManageUsers' => $currentUser->isSuperAdmin() || $currentUser->isAdmin(),
        ]);
    }

    /**
     * Display customer profile and full booking history.
     */
    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $bookings = Booking::with('court')
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->latest()
            ->paginate(10);

        return Inertia::render('admin/users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'created_at' => $user->created_at?->toFormattedDateString(),
            ],
            'bookings' => $bookings,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(array_column(RoleName::cases(), 'value'))],
        ]);

        /** @var User $currentUser */
        $currentUser = $request->user();

        // Admin cannot create super-admin or admin users
        if (! $currentUser->isSuperAdmin() && in_array($validated['role'], [RoleName::SuperAdmin->value, RoleName::Admin->value])) {
            abort(403, 'You cannot assign this role.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(array_column(RoleName::cases(), 'value'))],
        ]);

        /** @var User $currentUser */
        $currentUser = $request->user();

        // Admin cannot promote to super-admin or admin
        if (! $currentUser->isSuperAdmin() && in_array($validated['role'], [RoleName::SuperAdmin->value, RoleName::Admin->value])) {
            abort(403, 'You cannot assign this role.');
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            ...(! empty($validated['password']) ? ['password' => bcrypt($validated['password'])] : []),
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->assignedCourts()->detach();
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
