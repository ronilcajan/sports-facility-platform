<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Display the Super Admin Overview Dashboard.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Court::class);

        $totalCourts = Court::count();
        $activeCourts = Court::where('is_active', true)->count();
        $totalBookings = Booking::count();
        $pendingBookings = Booking::where('status', 'pending')->count();
        $totalRevenue = Booking::whereIn('status', ['approved', 'confirmed', 'completed'])->sum('total_price');
        $totalCustomers = User::role('customer')->count();

        // Recent 6 months trend data
        $monthsTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

            $monthBookings = Booking::whereBetween('created_at', [$monthStart, $monthEnd]);

            $monthsTrend[] = [
                'month' => $monthStart->format('M Y'),
                'bookings' => (clone $monthBookings)->count(),
                'revenue' => (float) (clone $monthBookings)->whereIn('status', ['approved', 'confirmed', 'completed'])->sum('total_price'),
            ];
        }

        // Courts breakdown list
        $courtsSummary = Court::withCount('staff')
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
        $recentBookings = Booking::with('court')
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'customer_name' => $booking->name,
                    'court_name' => $booking->court?->name ?? 'Deleted Court',
                    'date' => $booking->date,
                    'time_slots' => $booking->time_slots,
                    'total_price' => $booking->total_price,
                    'receipt_url' => $booking->receipt_url,
                    'status' => $booking->status,
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
}
