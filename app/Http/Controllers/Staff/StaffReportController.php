<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StaffReportController extends Controller
{
    /**
     * Display report statistics for assigned court only.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $assignedCourts = Court::visibleTo($user)->get();

        if ($assignedCourts->isEmpty()) {
            return Inertia::render('staff/reports/Index', [
                'assignedCourts' => [],
                'selectedCourt' => null,
                'reports' => null,
            ]);
        }

        $courtId = $request->input('court_id', $assignedCourts->first()->id);
        $selectedCourt = $assignedCourts->firstWhere('id', $courtId) ?? $assignedCourts->first();

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $bookingsQuery = Booking::where('court_id', $selectedCourt->id)
            ->whereBetween('date', [$startDate, $endDate]);

        $totalBookings = (clone $bookingsQuery)->count();
        $totalRevenue = (clone $bookingsQuery)->whereIn('status', ['approved', 'confirmed', 'completed'])->sum('total_price');
        $approvedBookings = (clone $bookingsQuery)->whereIn('status', ['approved', 'confirmed', 'completed'])->count();
        $pendingBookings = (clone $bookingsQuery)->where('status', 'pending')->count();
        $rejectedBookings = (clone $bookingsQuery)->where('status', 'rejected')->count();
        $cancelledBookings = (clone $bookingsQuery)->where('status', 'cancelled')->count();

        return Inertia::render('staff/reports/Index', [
            'assignedCourts' => $assignedCourts,
            'selectedCourt' => $selectedCourt,
            'reports' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalBookings' => $totalBookings,
                'totalRevenue' => (float) $totalRevenue,
                'approvedBookings' => $approvedBookings,
                'pendingBookings' => $pendingBookings,
                'rejectedBookings' => $rejectedBookings,
                'cancelledBookings' => $cancelledBookings,
            ],
        ]);
    }
}
