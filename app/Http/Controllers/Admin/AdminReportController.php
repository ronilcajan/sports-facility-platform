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

        $user = $request->user();

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());

        $bookingsQuery = Booking::whereBetween('date', [$startDate, $endDate]);

        // Venue admins see figures for their own venue only.
        if ($user->isVenueScopedAdmin()) {
            $bookingsQuery->visibleTo($user);
        }

        $totalBookings = (clone $bookingsQuery)->count();
        $totalRevenue = (clone $bookingsQuery)->whereIn('status', Booking::REVENUE_STATUSES)->sum('total_price');
        $approvedBookings = (clone $bookingsQuery)->whereIn('status', Booking::REVENUE_STATUSES)->count();
        $rejectedBookings = (clone $bookingsQuery)->where('status', 'rejected')->count();
        $cancelledBookings = (clone $bookingsQuery)->where('status', 'cancelled')->count();

        // Breakdown by court (scoped to the venue for admins), aggregated in SQL
        // rather than loading every booking of every court into memory.
        $courtBreakdown = Court::query()
            ->visibleTo($user)
            ->withCount(['bookings as total_bookings' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }])
            ->withCount(['bookings as approved_count' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate])
                    ->whereIn('status', Booking::REVENUE_STATUSES);
            }])
            ->withSum(['bookings as revenue' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate])
                    ->whereIn('status', Booking::REVENUE_STATUSES);
            }], 'total_price')
            ->get()
            ->map(function (Court $court): array {
                return [
                    'id' => $court->id,
                    'name' => $court->name,
                    'sport_type' => $court->sport_type->label(),
                    'total_bookings' => $court->total_bookings,
                    'approved_count' => $court->approved_count,
                    'revenue' => (float) ($court->revenue ?? 0),
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
