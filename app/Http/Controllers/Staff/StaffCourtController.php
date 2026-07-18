<?php

namespace App\Http\Controllers\Staff;

use App\Enums\CourtStatus;
use App\Enums\SportType;
use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class StaffCourtController extends Controller
{
    /**
     * Display courts accessible by the staff member.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Court::class);

        $user = $request->user();
        $courts = Court::visibleTo($user)
            ->with(['venue', 'primaryImage', 'images'])
            ->withCount('staff')
            ->latest('id')
            ->get();

        return Inertia::render('staff/courts/Index', [
            'courts' => $courts,
            'sportTypes' => array_map(
                fn (SportType $type): array => ['value' => $type->value, 'label' => $type->label()],
                SportType::cases(),
            ),
            'statuses' => array_map(
                fn (CourtStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                CourtStatus::cases(),
            ),
            'venues' => Venue::where('is_active', true)->select('id', 'name')->get(),
        ]);
    }

    /**
     * Create a new court (CR Court permission for Staff).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Court::class);

        $validated = $request->validate([
            'venue_id' => ['nullable', 'exists:venues,id'],
            'name' => ['required', 'string', 'max:255'],
            'sport_type' => ['required', new Enum(SportType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', new Enum(CourtStatus::class)],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'slot_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated['slug'] = $this->uniqueSlug($validated['name']);

        $court = Court::create($validated);

        $user = $request->user();
        if ($user->isStaff() && ! $user->canManageAllCourts()) {
            $user->assignedCourts()->attach($court->id);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Court created and assigned successfully.'),
        ]);

        return back();
    }

    /**
     * Update an assigned court.
     */
    public function update(Request $request, Court $court): RedirectResponse
    {
        $this->authorize('update', $court);

        $validated = $request->validate([
            'venue_id' => ['nullable', 'exists:venues,id'],
            'name' => ['required', 'string', 'max:255'],
            'sport_type' => ['required', new Enum(SportType::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', new Enum(CourtStatus::class)],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'slot_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'is_active' => ['required', 'boolean'],
        ]);

        $court->update($validated);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Court updated successfully.'),
        ]);

        return back();
    }

    /**
     * Generate a unique slug for the court.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Court::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
