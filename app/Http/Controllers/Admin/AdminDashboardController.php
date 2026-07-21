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

        // Daily trend data for the last 90 days (Area Chart)
        $dailyTrend = [];
        $startDate = Carbon::now()->subDays(89)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $allBookingsPeriod = $this->bookings($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        for ($d = 89; $d >= 0; $d--) {
            $day = Carbon::now()->subDays($d)->toDateString();
            $dayLabel = Carbon::now()->subDays($d)->format('M j');

            $dayBookings = $allBookingsPeriod->filter(fn ($b) => Carbon::parse($b->created_at)->toDateString() === $day);

            $confirmedCount = $dayBookings->filter(fn ($b) => in_array($b->status->value ?? $b->status, ['approved', 'confirmed', 'completed']))->count();
            $pendingCount = $dayBookings->filter(fn ($b) => ($b->status->value ?? $b->status) === 'pending')->count();
            $totalRevenueDay = (float) $dayBookings->filter(fn ($b) => in_array($b->status->value ?? $b->status, ['approved', 'confirmed', 'completed']))->sum('total_price');

            $dailyTrend[] = [
                'date' => $day,
                'label' => $dayLabel,
                'confirmed' => $confirmedCount,
                'pending' => $pendingCount,
                'revenue' => $totalRevenueDay,
            ];
        }

        // Sport types breakdown for Pie Chart
        $allUserCourts = $this->courts($user)->with(['bookings'])->get();
        $sportTypesBreakdown = [];
        foreach ($allUserCourts->groupBy('sport_type') as $sportEnum => $groupedCourts) {
            $sportLabel = is_object($sportEnum) && method_exists($sportEnum, 'label') ? $sportEnum->label() : (string) $sportEnum;
            $count = 0;
            $revenue = 0.0;

            foreach ($groupedCourts as $court) {
                $count += $court->bookings->count();
                $revenue += (float) $court->bookings->whereIn('status', ['approved', 'confirmed', 'completed'])->sum('total_price');
            }

            $sportTypesBreakdown[] = [
                'label' => ucfirst(str_replace('_', ' ', $sportLabel)),
                'count' => $count,
                'revenue' => $revenue,
            ];
        }

        // Booking status breakdown for Pie Chart
        $allBookings = $this->bookings($user)->get();
        $statusBreakdown = [
            ['label' => 'Approved / Confirmed', 'count' => $allBookings->filter(fn ($b) => in_array($b->status->value ?? $b->status, ['approved', 'confirmed', 'completed']))->count()],
            ['label' => 'Pending Approval', 'count' => $allBookings->filter(fn ($b) => ($b->status->value ?? $b->status) === 'pending')->count()],
            ['label' => 'Rejected', 'count' => $allBookings->filter(fn ($b) => ($b->status->value ?? $b->status) === 'rejected')->count()],
            ['label' => 'Cancelled', 'count' => $allBookings->filter(fn ($b) => ($b->status->value ?? $b->status) === 'cancelled')->count()],
        ];

        // Courts breakdown list
        $courtsSummary = $allUserCourts->map(function ($court) {
            return [
                'id' => $court->id,
                'name' => $court->name,
                'sport_type' => $court->sport_type->label(),
                'status' => $court->status->label(),
                'is_active' => $court->is_active,
                'staff_count' => $court->staff_count ?? 0,
                'total_bookings' => $court->bookings->count(),
                'total_revenue' => (float) $court->bookings->whereIn('status', ['approved', 'confirmed', 'completed'])->sum('total_price'),
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
            'dailyTrend' => $dailyTrend,
            'sportTypesBreakdown' => array_values($sportTypesBreakdown),
            'statusBreakdown' => $statusBreakdown,
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
