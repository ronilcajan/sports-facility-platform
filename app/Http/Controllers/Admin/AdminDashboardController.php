<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin overview dashboard, scoped to the admin's venue
     * (super-admins see platform-wide figures).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Court::class);

        /** @var User $user */
        $user = $request->user();

        $totalCourts = $this->courts($user)->count();
        $activeCourts = $this->courts($user)->where('is_active', true)->count();
        $totalBookings = $this->bookings($user)->count();
        $pendingBookings = $this->bookings($user)->where('status', 'pending')->count();
        $totalRevenue = $this->bookings($user)->whereIn('status', ['approved', 'confirmed', 'completed'])->sum('total_price');
        $totalCustomers = $this->customerCount($user);

        // Recent 6 months trend data
        $monthsTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

            $monthBookings = $this->bookings($user)->whereBetween('created_at', [$monthStart, $monthEnd]);

            $monthsTrend[] = [
                'month' => $monthStart->format('M Y'),
                'bookings' => (clone $monthBookings)->count(),
                'revenue' => (float) (clone $monthBookings)->whereIn('status', ['approved', 'confirmed', 'completed'])->sum('total_price'),
            ];
        }

        // Courts breakdown list
        $courtsSummary = $this->courts($user)
            ->withCount('staff')
            ->withCount(['bookings as total_bookings'])
            ->withSum(['bookings as total_revenue' => function ($query) {
                $query->whereIn('status', ['approved', 'confirmed', 'completed']);
            }], 'total_price')
            ->get()
            ->map(function ($court) {
                return [
                    'id' => $court->id,
                    'name' => $court->name,
                    'sport_type' => $court->sport_type->label(),
                    'status' => $court->status->label(),
                    'is_active' => $court->is_active,
                    'staff_count' => $court->staff_count,
                    'total_bookings' => $court->total_bookings,
                    'total_revenue' => (float) ($court->total_revenue ?? 0),
                ];
            });

        // Recent bookings
        $recentBookings = $this->bookings($user)
            ->with(['court.venue'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'reference_code' => 'DY-RESRV-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
                    'customer_name' => $booking->name,
                    'email' => $booking->email,
                    'phone' => $booking->phone,
                    'court_name' => $booking->court?->name ?? 'Deleted Court',
                    'sport_type' => $booking->court?->sport_type?->label() ?? 'N/A',
                    'venue_name' => $booking->court?->venue?->name ?? 'N/A',
                    'date' => (string) $booking->date,
                    'time_slots' => $booking->time_slots,
                    'total_price' => number_format((float) $booking->total_price, 2, '.', ''),
                    'receipt_url' => $booking->receipt_url,
                    'status' => $booking->status,
                    'notes' => $booking->notes,
                    'created_at' => $booking->created_at?->toDateTimeString(),
                ];
            });

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'totalCourts' => $totalCourts,
                'activeCourts' => $activeCourts,
                'totalBookings' => $totalBookings,
                'pendingBookings' => $pendingBookings,
                'totalRevenue' => (float) $totalRevenue,
                'totalCustomers' => $totalCustomers,
            ],
            'monthsTrend' => $monthsTrend,
            'courtsSummary' => $courtsSummary,
            'recentBookings' => $recentBookings,
        ]);
    }

    /**
     * Fresh court query scoped to what the user may see.
     *
     * @return Builder<Court>
     */
    private function courts(User $user): Builder
    {
        return $user->isVenueScopedAdmin()
            ? Court::query()->visibleTo($user)
            : Court::query();
    }

    /**
     * Fresh booking query scoped to what the user may see.
     *
     * @return Builder<Booking>
     */
    private function bookings(User $user): Builder
    {
        return $user->isVenueScopedAdmin()
            ? Booking::query()->visibleTo($user)
            : Booking::query();
    }

    /**
     * Count registered customers — all of them for super-admins, or those who
     * have booked at the admin's venue.
     */
    private function customerCount(User $user): int
    {
        if ($user->isVenueScopedAdmin()) {
            return User::role(RoleName::Customer->value)
                ->whereHas('bookings', fn (Builder $query) => $query->visibleTo($user))
                ->count();
        }

        return User::role(RoleName::Customer->value)->count();
    }
}
