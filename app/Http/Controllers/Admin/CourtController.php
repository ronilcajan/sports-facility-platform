<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourtStatus;
use App\Enums\SportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourtRequest;
use App\Http\Requests\Admin\UpdateCourtRequest;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Venue;
use App\Support\ImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourtController extends Controller
{
    /**
     * Display a listing of the courts the current user may see.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Court::class);

        $user = request()->user();

        $courts = Court::query()
            ->visibleTo($user)
            ->with(['venue', 'primaryImage', 'images'])
            ->withCount('staff')
            ->latest('id')
            ->get();

        return Inertia::render('admin/courts/Index', [
            'courts' => $courts,
            'sportTypes' => $this->sportTypeOptions(),
            'statuses' => $this->statusOptions(),
            // Venue admins can only file courts under their own venue.
            'venues' => Venue::where('is_active', true)
                ->when($user->isVenueScopedAdmin(), fn ($query) => $query->whereKey($user->venue_id))
                ->select('id', 'name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new court.
     */
    public function create(): Response
    {
        $this->authorize('create', Court::class);

        return Inertia::render('admin/courts/Create', [
            'sportTypes' => $this->sportTypeOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Store a newly created court.
     */
    public function store(StoreCourtRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);

        // Venue admins can only create courts within their own venue.
        if ($request->user()->isVenueScopedAdmin()) {
            $data['venue_id'] = $request->user()->venue_id;
        }

        unset($data['image'], $data['delete_image']);
        $court = Court::create($data);

        if ($request->hasFile('image')) {
            $path = ImageStorage::photo($request->file('image'), 'courts');
            $court->images()->create([
                'path' => $path,
                'is_primary' => true,
                'sort_order' => 1,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court created.')]);

        return to_route('admin.courts.index');
    }

    /**
     * Display the specified court profile page with details, venue, bookings, and schedule.
     */
    public function show(Request $request, Court $court): Response
    {
        $this->authorize('view', $court);

        $court->load(['venue', 'primaryImage', 'images', 'staff']);

        $query = Booking::query()
            ->where('court_id', $court->id)
            ->with(['user'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $bookings = $query->paginate(15)->through(fn (Booking $booking) => [
            'id' => $booking->id,
            'reference_code' => 'DY-RESRV-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'court_name' => $court->name,
            'sport_type' => $court->sport_type?->label() ?? $court->sport_type,
            'name' => $booking->name,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'date' => $booking->date->toDateString(),
            'time_slots' => $booking->time_slots,
            'total_price' => number_format((float) $booking->total_price, 2, '.', ''),
            'receipt_url' => $booking->receipt_url,
            'status' => $booking->status,
            'notes' => $booking->notes,
            'created_at' => $booking->created_at?->toFormattedDateString(),
        ])->withQueryString();

        return Inertia::render('admin/courts/Show', [
            'court' => [
                'id' => $court->id,
                'venue_id' => $court->venue_id,
                'name' => $court->name,
                'slug' => $court->slug,
                'sport_type' => $court->sport_type?->label() ?? $court->sport_type,
                'description' => $court->description,
                'base_price' => (string) $court->base_price,
                'slot_prices' => $court->slot_prices,
                'slot_duration_minutes' => $court->slot_duration_minutes,
                'buffer_minutes' => $court->buffer_minutes,
                'is_active' => $court->is_active,
                'status' => $court->status?->value ?? $court->status,
                'primary_image_url' => $court->primaryImage ? (str_starts_with($court->primaryImage->path, 'http') ? $court->primaryImage->path : asset('storage/'.$court->primaryImage->path)) : null,
                'venue' => $court->venue ? [
                    'id' => $court->venue->id,
                    'name' => $court->venue->name,
                    'slug' => $court->venue->slug,
                    'address' => $court->venue->address,
                    'phone' => $court->venue->phone,
                    'email' => $court->venue->email,
                ] : null,
                'images' => $court->images->map(fn ($img) => [
                    'id' => $img->id,
                    'path' => $img->path,
                    'url' => $img->url,
                    'is_primary' => $img->is_primary,
                    'sort_order' => $img->sort_order,
                ]),
                'staff' => $court->staff->map(fn ($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
                'created_at' => $court->created_at?->toFormattedDateString(),
            ],
            'bookings' => $bookings,
            'filters' => $request->only(['search', 'status']),
            'canDelete' => $request->user()?->isSuperAdmin() ?? false,
            'canManageCourt' => $request->user()?->isSuperAdmin() || $request->user()?->canManageAllCourts() || ($request->user()?->venue_id === $court->venue_id),
        ]);
    }

    /**
     * Show the form for editing the specified court.
     */
    public function edit(Court $court): Response
    {
        $this->authorize('update', $court);

        return Inertia::render('admin/courts/Edit', [
            'court' => $court,
            'sportTypes' => $this->sportTypeOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Update the specified court.
     */
    public function update(UpdateCourtRequest $request, Court $court): RedirectResponse
    {
        $data = $request->validated();

        if ($data['name'] !== $court->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $court);
        }

        // Venue admins cannot move a court to a different venue.
        if ($request->user()->isVenueScopedAdmin()) {
            $data['venue_id'] = $court->venue_id;
        }

        unset($data['image'], $data['delete_image']);
        $court->update($data);

        if ($request->hasFile('image')) {
            $path = ImageStorage::photo($request->file('image'), 'courts');
            $court->images()->update(['is_primary' => false]);
            $maxSortOrder = $court->images()->max('sort_order') ?? 0;
            $court->images()->create([
                'path' => $path,
                'is_primary' => true,
                'sort_order' => $maxSortOrder + 1,
            ]);
        } elseif ($request->boolean('delete_image')) {
            $primaryImage = $court->primaryImage;
            if ($primaryImage) {
                if (Storage::disk('public')->exists($primaryImage->path)) {
                    Storage::disk('public')->delete($primaryImage->path);
                }
                $primaryImage->delete();
                $next = $court->images()->first();
                if ($next) {
                    $next->update(['is_primary' => true]);
                }
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court updated.')]);

        return to_route('admin.courts.index');
    }

    /**
     * Remove the specified court.
     */
    public function destroy(Court $court): RedirectResponse
    {
        $this->authorize('delete', $court);

        $court->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court deleted.')]);

        return to_route('admin.courts.index');
    }

    /**
     * Generate a slug from the name that is unique across courts.
     */
    private function uniqueSlug(string $name, ?Court $ignore = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Court::withTrashed()
            ->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Sport type options for select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function sportTypeOptions(): array
    {
        return array_map(
            fn (SportType $type): array => ['value' => $type->value, 'label' => $type->label()],
            SportType::cases(),
        );
    }

    /**
     * Court status options for select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (CourtStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            CourtStatus::cases(),
        );
    }
}
