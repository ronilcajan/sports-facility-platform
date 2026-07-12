<?php

namespace App\Http\Controllers\Site;

use App\Enums\CourtStatus;
use App\Http\Controllers\Controller;
use App\Models\Court;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * The marketing homepage.
     */
    public function home(): Response
    {
        return Inertia::render('site/Home', [
            'content' => config('site_content.home'),
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
     * Public court listing, sourced from real bookable courts.
     */
    public function courts(): Response
    {
        return Inertia::render('site/Courts', [
            'courts' => $this->bookableCourts(),
        ]);
    }

    /**
     * Show a detailed page for a specific court.
     */
    public function show(Court $court): Response
    {
        $court->load(['primaryImage', 'images']);

        return Inertia::render('site/CourtShow', [
            'court' => [
                'id' => $court->id,
                'name' => $court->name,
                'slug' => $court->slug,
                'sport_type' => $court->sport_type->label(),
                'description' => $court->description,
                'base_price' => $court->base_price,
                'slot_duration_minutes' => $court->slot_duration_minutes,
                'primary_image_url' => $court->primaryImage ? asset('storage/'.$court->primaryImage->path) : null,
                'images' => $court->images->map(fn ($img) => asset('storage/'.$img->path)),
            ],
            'relatedCourts' => $this->bookableCourts()->reject(fn ($c) => $c['id'] === $court->id)->take(3)->values(),
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
     * Courts that are available and active, shaped for public display.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function bookableCourts(?int $limit = null): Collection
    {
        return Court::query()
            ->with('primaryImage')
            ->where('status', CourtStatus::Available)
            ->where('is_active', true)
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
                'primary_image_url' => $court->primaryImage ? asset('storage/'.$court->primaryImage->path) : null,
            ]);
    }
}
