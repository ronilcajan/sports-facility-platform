<?php

namespace App\Http\Controllers\Staff;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtUnavailability;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StaffDashboardController extends Controller
{
    /**
     * Display the Court Staff Dashboard for assigned court(s).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get courts visible to this staff user
        $assignedCourts = Court::visibleTo($user)->get();

        if ($assignedCourts->isEmpty()) {
            return Inertia::render('staff/Dashboard', [
                'assignedCourts' => [],
                'selectedCourt' => null,
                'hasNoCourts' => true,
                'stats' => [
                    'totalCourts' => 0,
                    'activeCourts' => 0,
                    'totalBookings' => 0,
                    'pendingBookings' => 0,
                    'totalRevenue' => 0.0,
                    'totalCustomers' => 0,
                ],
                'monthsTrend' => [],
                'courtsSummary' => [],
                'recentBookings' => [],
                'todayBookings' => [],
                'pendingBookings' => [],
                'unavailabilities' => [],
                'unreadNotifications' => [],
            ]);
        }

        $courtId = $request->input('court_id', $assignedCourts->first()->id);
        $selectedCourt = $assignedCourts->firstWhere('id', $courtId) ?? $assignedCourts->first();

        $today = Carbon::today()->toDateString();

        $totalCourts = Court::visibleTo($user)->count();
        $activeCourts = Court::visibleTo($user)->where('is_active', true)->count();
        $totalBookings = Booking::visibleTo($user)->count();
        $pendingBookingsCount = Booking::visibleTo($user)->where('status', 'pending')->count();
        $totalRevenue = Booking::visibleTo($user)->whereIn('status', Booking::REVENUE_STATUSES)->sum('total_price');
        $totalCustomers = User::role(RoleName::Customer->value)
            ->whereHas('bookings', fn (Builder $query) => $query->visibleTo($user))
            ->count();

        // 6 months trend
        $monthsTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

            $monthBookings = Booking::visibleTo($user)->whereBetween('created_at', [$monthStart, $monthEnd]);

            $monthsTrend[] = [
                'month' => $monthStart->format('M Y'),
                'bookings' => (clone $monthBookings)->count(),
                'revenue' => (float) (clone $monthBookings)->whereIn('status', Booking::REVENUE_STATUSES)->sum('total_price'),
            ];
        }

        // Courts summary
        $courtsSummary = Court::visibleTo($user)
            ->withCount('staff')
            ->withCount(['bookings as total_bookings'])
            ->withSum(['bookings as total_revenue' => function ($query) {
                $query->whereIn('status', Booking::REVENUE_STATUSES);
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
        $recentBookings = Booking::visibleTo($user)
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

        // Today's reservations for assigned court
        $todayBookings = Booking::where('court_id', $selectedCourt->id)
            ->where('date', $today)
            ->get();

        // Pending approval queue for assigned court
        $pendingBookingsList = Booking::where('court_id', $selectedCourt->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        // Court unavailabilities/blackouts
        $unavailabilities = CourtUnavailability::where('court_id', $selectedCourt->id)
            ->where('date', '>=', $today)
            ->orderBy('date')
            ->get();

        // Unread notifications count for staff
        $unreadNotifications = $user->unreadNotifications()
            ->where('data->court_id', $selectedCourt->id)
            ->get();

        return Inertia::render('staff/Dashboard', [
            'assignedCourts' => $assignedCourts,
            'selectedCourt' => $selectedCourt->load('primaryImage'),
            'hasNoCourts' => false,
            'stats' => [
                'totalCourts' => $totalCourts,
                'activeCourts' => $activeCourts,
                'totalBookings' => $totalBookings,
                'pendingBookings' => $pendingBookingsCount,
                'totalRevenue' => (float) $totalRevenue,
                'totalCustomers' => $totalCustomers,
            ],
            'monthsTrend' => $monthsTrend,
            'courtsSummary' => $courtsSummary,
            'recentBookings' => $recentBookings,
            'todayBookings' => $todayBookings,
            'pendingBookings' => $pendingBookingsList,
            'unavailabilities' => $unavailabilities,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }
}
