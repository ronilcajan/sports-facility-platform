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
    'site.faqs',
    'site.contact',
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

test('contact form accepts a valid submission', function () {
    $this->post(route('site.contact.store'), [
        'name' => 'Jordan Rivera',
        'email' => 'jordan@example.com',
        'message' => 'I would like to book a court for a group of eight this weekend.',
    ])->assertRedirect();
});

test('contact form validates its fields', function () {
    $this->post(route('site.contact.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'message' => 'too short',
    ])->assertSessionHasErrors(['name', 'email', 'message']);
});
