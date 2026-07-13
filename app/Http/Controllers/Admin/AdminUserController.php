<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
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

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'roles' => RoleName::cases(),
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
}
