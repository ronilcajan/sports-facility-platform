<?php

namespace App\Http\Controllers\Site;

use App\Enums\CourtStatus;
use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * The marketing homepage displaying venues first.
     */
    public function home(): Response
    {
        return Inertia::render('site/Home', [
            'content' => config('site_content.home'),
            'venues' => $this->venueCatalog(),
            'featuredCourts' => $this->bookableCourts(3),
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('site/About', [
            'content' => config('site_content.about'),
        ]);
    }

    /**
     * Public venue & court listing — Venue-First layout.
     */
    public function courts(Request $request): Response
    {
        $venueId = $request->query('venue') ? (int) $request->query('venue') : null;

        return Inertia::render('site/Courts', [
            'venues' => $this->venueCatalog(),
            'courts' => $this->bookableCourts(null, $venueId),
            'selectedVenueId' => $venueId,
        ]);
    }

    /**
     * Show a detailed page for a specific court including its Venue information.
     */
    public function show(Court $court): Response
    {
        $court->load(['primaryImage', 'images', 'venue']);

        $relatedCourts = $this->bookableCourts()
            ->reject(fn ($c) => $c['id'] === $court->id);

        // Prioritize courts from the same venue if available
        if ($court->venue_id) {
            $sameVenue = $relatedCourts->filter(fn ($c) => isset($c['venue']['id']) && $c['venue']['id'] === $court->venue_id);
            if ($sameVenue->count() > 0) {
                $relatedCourts = $sameVenue->concat($relatedCourts->reject(fn ($c) => isset($c['venue']['id']) && $c['venue']['id'] === $court->venue_id));
            }
        }

        return Inertia::render('site/CourtShow', [
            'court' => [
                'id' => $court->id,
                'name' => $court->name,
                'slug' => $court->slug,
                'sport_type' => $court->sport_type->label(),
                'description' => $court->description,
                'base_price' => $court->base_price,
                'slot_duration_minutes' => $court->slot_duration_minutes,
                'primary_image_url' => $court->primaryImage ? (str_starts_with($court->primaryImage->path, 'http') ? $court->primaryImage->path : asset('storage/'.$court->primaryImage->path)) : null,
                'images' => $court->images->map(fn ($img) => str_starts_with($img->path, 'http') ? $img->path : asset('storage/'.$img->path)),
                'venue' => $court->venue ? [
                    'id' => $court->venue->id,
                    'name' => $court->venue->name,
                    'slug' => $court->venue->slug,
                    'address' => $court->venue->address,
                    'phone' => $court->venue->phone,
                    'email' => $court->venue->email,
                    'description' => $court->venue->description,
                ] : null,
            ],
            'relatedCourts' => $relatedCourts->take(3)->values(),
        ]);
    }

    /**
     * Show a detailed page for a specific Venue including all its courts and court images.
     */
    public function venueShow(Venue $venue): Response
    {
        $venue->load(['courts' => function ($q) {
            $q->where('status', CourtStatus::Available)
                ->where('is_active', true)
                ->with(['primaryImage', 'images'])
                ->orderBy('name');
        }]);

        $bookableCourts = $venue->courts->map(fn (Court $court) => [
            'id' => $court->id,
            'name' => $court->name,
            'slug' => $court->slug,
            'sport_type' => $court->sport_type->label(),
            'description' => $court->description,
            'base_price' => $court->base_price,
            'slot_duration_minutes' => $court->slot_duration_minutes,
            'primary_image_url' => $court->primaryImage ? (str_starts_with($court->primaryImage->path, 'http') ? $court->primaryImage->path : asset('storage/'.$court->primaryImage->path)) : null,
            'images' => $court->images->map(fn ($img) => str_starts_with($img->path, 'http') ? $img->path : asset('storage/'.$img->path)),
            'venue' => [
                'id' => $venue->id,
                'name' => $venue->name,
                'slug' => $venue->slug,
                'address' => $venue->address,
            ],
        ]);

        $allCourtImages = $bookableCourts->pluck('images')->flatten()->unique()->values();
        $coverImage = $bookableCourts->firstWhere('primary_image_url', '!==', null)['primary_image_url'] ?? asset('images/hero_pickleball.png');

        return Inertia::render('site/VenueShow', [
            'venue' => [
                'id' => $venue->id,
                'name' => $venue->name,
                'slug' => $venue->slug,
                'description' => $venue->description,
                'address' => $venue->address,
                'phone' => $venue->phone,
                'email' => $venue->email,
                'cover_image_url' => $coverImage,
                'courts_count' => $bookableCourts->count(),
                'courts' => $bookableCourts->values(),
                'images' => $allCourtImages,
            ],
            'venues' => $this->venueCatalog(),
        ]);
    }

    public function pricing(): Response
    {
        return Inertia::render('site/Pricing', [
            'content' => config('site_content.pricing'),
        ]);
    }

    public function gallery(): Response
    {
        return Inertia::render('site/Gallery', [
            'content' => config('site_content.gallery'),
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('site/Privacy', [
            'content' => config('site_content.privacy'),
        ]);
    }

    public function terms(): Response
    {
        return Inertia::render('site/Terms', [
            'content' => config('site_content.terms'),
        ]);
    }

    /**
     * Venue catalog with assigned active courts.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function venueCatalog(): Collection
    {
        return Venue::query()
            ->where('is_active', true)
            ->with(['courts' => function ($q) {
                $q->where('status', CourtStatus::Available)
                    ->where('is_active', true)
                    ->with('primaryImage')
                    ->orderBy('name');
            }])
            ->orderBy('name')
            ->get()
            ->map(function (Venue $venue) {
                $bookableCourts = $venue->courts->map(fn (Court $court) => [
                    'id' => $court->id,
                    'name' => $court->name,
                    'slug' => $court->slug,
                    'sport_type' => $court->sport_type->label(),
                    'description' => $court->description,
                    'base_price' => $court->base_price,
                    'slot_duration_minutes' => $court->slot_duration_minutes,
                    'primary_image_url' => $court->primaryImage ? (str_starts_with($court->primaryImage->path, 'http') ? $court->primaryImage->path : asset('storage/'.$court->primaryImage->path)) : null,
                    'venue' => [
                        'id' => $venue->id,
                        'name' => $venue->name,
                        'slug' => $venue->slug,
                        'address' => $venue->address,
                    ],
                ]);

                $firstImage = $bookableCourts->firstWhere('primary_image_url', '!==', null)['primary_image_url'] ?? null;

                return [
                    'id' => $venue->id,
                    'name' => $venue->name,
                    'slug' => $venue->slug,
                    'description' => $venue->description,
                    'address' => $venue->address,
                    'phone' => $venue->phone,
                    'email' => $venue->email,
                    'cover_image_url' => $firstImage ?: asset('images/hero_pickleball.png'),
                    'courts_count' => $bookableCourts->count(),
                    'courts' => $bookableCourts->values(),
                ];
            });
    }

    /**
     * Courts that are available and active, shaped for public display.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function bookableCourts(?int $limit = null, ?int $venueId = null): Collection
    {
        return Court::query()
            ->with(['primaryImage', 'venue'])
            ->where('status', CourtStatus::Available)
            ->where('is_active', true)
            ->when($venueId, fn ($query) => $query->where('venue_id', $venueId))
            ->orderBy('name')
            ->when($limit, fn ($query) => $query->limit($limit))
            ->get()
            ->map(fn (Court $court): array => [
                'id' => $court->id,
                'name' => $court->name,
                'slug' => $court->slug,
                'sport_type' => $court->sport_type->label(),
                'description' => $court->description,
                'base_price' => $court->base_price,
                'slot_duration_minutes' => $court->slot_duration_minutes,
                'primary_image_url' => $court->primaryImage ? (str_starts_with($court->primaryImage->path, 'http') ? $court->primaryImage->path : asset('storage/'.$court->primaryImage->path)) : null,
                'venue' => $court->venue ? [
                    'id' => $court->venue->id,
                    'name' => $court->venue->name,
                    'slug' => $court->venue->slug,
                    'address' => $court->venue->address,
                ] : null,
            ]);
    }
}
