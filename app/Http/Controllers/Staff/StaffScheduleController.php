<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtUnavailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class StaffScheduleController extends Controller
{
    /**
     * Display reservations and schedule management for assigned court.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $assignedCourts = Court::visibleTo($user)->get();

        if ($assignedCourts->isEmpty()) {
            return Inertia::render('staff/schedules/Index', [
                'assignedCourts' => [],
                'selectedCourt' => null,
                'bookings' => [],
                'unavailabilities' => [],
            ]);
        }

        $courtId = $request->input('court_id', $assignedCourts->first()->id);
        $selectedCourt = $assignedCourts->firstWhere('id', $courtId) ?? $assignedCourts->first();

        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startOfMonth = Carbon::parse($month)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::parse($month)->endOfMonth()->toDateString();

        $bookings = Booking::where('court_id', $selectedCourt->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $unavailabilities = CourtUnavailability::where('court_id', $selectedCourt->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        return Inertia::render('staff/schedules/Index', [
            'assignedCourts' => $assignedCourts,
            'selectedCourt' => $selectedCourt,
            'currentMonth' => $month,
            'bookings' => $bookings,
            'unavailabilities' => $unavailabilities,
        ]);
    }

    /**
     * Create an unavailable blackout slot for an assigned court.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'court_id' => ['required', 'exists:courts,id'],
            'date' => ['required', 'date'],
            'start_time' => ['nullable', 'string'],
            'end_time' => ['nullable', 'string'],
            'all_day' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $court = Court::findOrFail($validated['court_id']);
        $this->authorize('manage', [CourtUnavailability::class, $court]);

        CourtUnavailability::create([
            'court_id' => $court->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'all_day' => $validated['all_day'],
            'reason' => $validated['reason'] ?? __('Maintenance / Closed'),
            'created_by' => $user->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Unavailable date/time added.')]);

        return back();
    }
}
