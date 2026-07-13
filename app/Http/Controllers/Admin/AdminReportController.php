<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminReportController extends Controller
{
    /**
     * Display report analytics for Super Admin.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Court::class);

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $bookingsQuery = Booking::whereBetween('date', [$startDate, $endDate]);

        $totalBookings = (clone $bookingsQuery)->count();
        $totalRevenue = (clone $bookingsQuery)->whereIn('status', ['approved', 'confirmed', 'completed'])->sum('total_price');
        $approvedBookings = (clone $bookingsQuery)->whereIn('status', ['approved', 'confirmed', 'completed'])->count();
        $rejectedBookings = (clone $bookingsQuery)->where('status', 'rejected')->count();
        $cancelledBookings = (clone $bookingsQuery)->where('status', 'cancelled')->count();

        // Breakdown by court
        $courtBreakdown = Court::with(['bookings' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }])->get()->map(function ($court) {
            $approved = $court->bookings->whereIn('status', ['approved', 'confirmed', 'completed']);

            return [
                'id' => $court->id,
                'name' => $court->name,
                'sport_type' => $court->sport_type->label(),
                'total_bookings' => $court->bookings->count(),
                'approved_count' => $approved->count(),
                'revenue' => (float) $approved->sum('total_price'),
            ];
        });

        return Inertia::render('admin/reports/Index', [
            'reports' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalBookings' => $totalBookings,
                'totalRevenue' => (float) $totalRevenue,
                'approvedBookings' => $approvedBookings,
                'rejectedBookings' => $rejectedBookings,
                'cancelledBookings' => $cancelledBookings,
                'courtBreakdown' => $courtBreakdown,
            ],
        ]);
    }
}
