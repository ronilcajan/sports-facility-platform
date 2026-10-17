<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Expense;
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

        // Expenses in the selected period
        $expensesQuery = Expense::whereBetween('date', [$startDate, $endDate]);
        if ($user->isVenueScopedAdmin()) {
            $expensesQuery->where('venue_id', $user->venue_id);
        }

        $totalExpenses = (float) (clone $expensesQuery)->sum('amount');
        $netProfit = (float) ($totalRevenue - $totalExpenses);

        $expenseCategories = (clone $expensesQuery)
            ->selectRaw('category, SUM(amount) as total_amount, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row): array => [
                'category' => $row->category,
                'label' => Expense::categoryLabel($row->category),
                'total_amount' => (float) $row->total_amount,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

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
                'totalExpenses' => (float) $totalExpenses,
                'netProfit' => (float) $netProfit,
                'approvedBookings' => $approvedBookings,
                'rejectedBookings' => $rejectedBookings,
                'cancelledBookings' => $cancelledBookings,
                'courtBreakdown' => $courtBreakdown,
                'expenseCategories' => $expenseCategories,
            ],
        ]);
    }
}
