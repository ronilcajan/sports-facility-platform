<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtUnavailability;
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
            ]);
        }

        $courtId = $request->input('court_id', $assignedCourts->first()->id);
        $selectedCourt = $assignedCourts->firstWhere('id', $courtId) ?? $assignedCourts->first();

        $today = Carbon::today()->toDateString();

        // Today's reservations for assigned court
        $todayBookings = Booking::where('court_id', $selectedCourt->id)
            ->where('date', $today)
            ->get();

        // Pending approval queue for assigned court
        $pendingBookings = Booking::where('court_id', $selectedCourt->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        // Total revenue for assigned court
        $courtRevenue = Booking::where('court_id', $selectedCourt->id)
            ->whereIn('status', ['approved', 'confirmed', 'completed'])
            ->sum('total_price');

        // Total bookings count for assigned court
        $totalCourtBookings = Booking::where('court_id', $selectedCourt->id)->count();

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
                'todayBookingsCount' => $todayBookings->count(),
                'pendingCount' => $pendingBookings->count(),
                'totalBookings' => $totalCourtBookings,
                'totalRevenue' => (float) $courtRevenue,
            ],
            'todayBookings' => $todayBookings,
            'pendingBookings' => $pendingBookings,
            'unavailabilities' => $unavailabilities,
            'unreadNotifications' => $unreadNotifications,
        ]);
    }
}
