<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Support\TrafficAnalytics;
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
        $totalRevenue = $this->bookings($user)->whereIn('status', Booking::REVENUE_STATUSES)->sum('total_price');
        $totalCustomers = $this->customerCount($user);

        // Daily trend data for the last 90 days (Area Chart). Bookings for the
        // window are fetched once and bucketed by day, rather than re-scanning
        // the whole collection for each of the 90 days.
        $dailyTrend = [];
        $startDate = Carbon::now()->subDays(89)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $bookingsByDay = $this->bookings($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['id', 'created_at', 'status', 'total_price'])
            ->groupBy(fn (Booking $booking): string => $booking->created_at->toDateString());

        for ($d = 89; $d >= 0; $d--) {
            $day = Carbon::now()->subDays($d);
            $dateString = $day->toDateString();
            $dayBookings = $bookingsByDay->get($dateString, collect());
            $revenueBookings = $dayBookings->whereIn('status', Booking::REVENUE_STATUSES);

            $dailyTrend[] = [
                'date' => $dateString,
                'label' => $day->format('M j'),
                'confirmed' => $revenueBookings->count(),
                'pending' => $dayBookings->where('status', 'pending')->count(),
                'revenue' => (float) $revenueBookings->sum('total_price'),
            ];
        }

        // Per-court totals resolved as SQL aggregates instead of loading every
        // booking of every court into memory.
        $allUserCourts = $this->courts($user)
            ->withCount('staff')
            ->withCount('bookings as total_bookings')
            ->withSum([
                'bookings as total_revenue' => fn ($query) => $query->whereIn('status', Booking::REVENUE_STATUSES),
            ], 'total_price')
            ->get();

        // Sport types breakdown for Pie Chart
        $sportTypesBreakdown = $allUserCourts
            ->groupBy(fn (Court $court): string => $court->sport_type->label())
            ->map(fn ($groupedCourts, string $sportLabel): array => [
                'label' => ucfirst(str_replace('_', ' ', $sportLabel)),
                'count' => (int) $groupedCourts->sum('total_bookings'),
                'revenue' => (float) $groupedCourts->sum('total_revenue'),
            ])
            ->values()
            ->all();

        // Booking status breakdown for Pie Chart — one query, conditional counts.
        $statusCounts = $this->bookings($user)->toBase()
            ->selectRaw('count(case when status in (?, ?, ?) then 1 end) as revenue_count', Booking::REVENUE_STATUSES)
            ->selectRaw('count(case when status = ? then 1 end) as pending_count', ['pending'])
            ->selectRaw('count(case when status = ? then 1 end) as rejected_count', ['rejected'])
            ->selectRaw('count(case when status = ? then 1 end) as cancelled_count', ['cancelled'])
            ->first();

        $statusBreakdown = [
            ['label' => 'Approved / Confirmed', 'count' => (int) $statusCounts->revenue_count],
            ['label' => 'Pending Approval', 'count' => (int) $statusCounts->pending_count],
            ['label' => 'Rejected', 'count' => (int) $statusCounts->rejected_count],
            ['label' => 'Cancelled', 'count' => (int) $statusCounts->cancelled_count],
        ];

        // Courts breakdown list
        $courtsSummary = $allUserCourts->map(fn (Court $court): array => [
            'id' => $court->id,
            'name' => $court->name,
            'sport_type' => $court->sport_type->label(),
            'status' => $court->status->label(),
            'is_active' => $court->is_active,
            'staff_count' => $court->staff_count,
            'total_bookings' => $court->total_bookings,
            'total_revenue' => (float) ($court->total_revenue ?? 0),
        ]);

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
                    'date' => $booking->date->toDateString(),
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
            'dailyTrend' => $dailyTrend,
            'sportTypesBreakdown' => array_values($sportTypesBreakdown),
            'statusBreakdown' => $statusBreakdown,
            'courtsSummary' => $courtsSummary,
            'recentBookings' => $recentBookings,
            'trafficAnalytics' => TrafficAnalytics::build(),
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
