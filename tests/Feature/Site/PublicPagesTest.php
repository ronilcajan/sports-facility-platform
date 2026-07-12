<?php

use App\Models\Court;

test('public pages are reachable by guests', function (string $routeName) {
    $this->get(route($routeName))->assertOk();
})->with([
    'home',
    'site.about',
    'site.courts',
    'site.pricing',
    'site.gallery',
    'site.privacy',
    'site.terms',
]);

test('homepage shows only bookable courts', function () {
    Court::factory()->available()->create(['name' => 'Open Court', 'is_active' => true]);
    Court::factory()->maintenance()->create(['name' => 'Down Court']);
    Court::factory()->available()->create(['name' => 'Hidden Court', 'is_active' => false]);

    $this->get(route('home'))
        ->assertInertia(
            fn ($page) => $page
                ->component('site/Home')
                ->has('featuredCourts', 1)
                ->where('featuredCourts.0.name', 'Open Court')
        );
});

test('courts page lists every bookable court', function () {
    Court::factory()->available()->count(4)->create();
    Court::factory()->closed()->create();

    $this->get(route('site.courts'))
        ->assertInertia(fn ($page) => $page->has('courts', 4));
});

test('court details page renders correctly with its images', function () {
    $court = Court::factory()->available()->create([
        'name' => 'Center Court',
        'is_active' => true,
    ]);

    // Create a primary image record in db
    $court->images()->create([
        'path' => 'courts/court_pickleball.png',
        'is_primary' => true,
        'sort_order' => 0,
    ]);

    // Create a secondary image record in db
    $court->images()->create([
        'path' => 'courts/hero_pickleball.png',
        'is_primary' => false,
        'sort_order' => 1,
    ]);

    $this->get(route('site.courts.show', $court))
        ->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('site/CourtShow')
                ->has('court')
                ->where('court.name', 'Center Court')
                ->where('court.primary_image_url', asset('storage/courts/court_pickleball.png'))
                ->has('court.images', 2)
                ->where('court.images.0', asset('storage/courts/court_pickleball.png'))
                ->where('court.images.1', asset('storage/courts/hero_pickleball.png'))
        );
});
