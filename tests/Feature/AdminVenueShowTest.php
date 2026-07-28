<?php

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;

test('super admin can view venue profile page', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $venue = Venue::factory()->create();
    $court = Court::factory()->for($venue)->create();

    $response = $this->actingAs($superAdmin)
        ->get(route('admin.venues.show', $venue));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/venues/Show')
        ->has('venue')
        ->has('courts')
        ->has('bookings')
    );
});

test('venue profile page includes courts for the venue', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $venue = Venue::factory()->create();
    $court = Court::factory()->for($venue)->create();

    $otherVenue = Venue::factory()->create();
    Court::factory()->for($otherVenue)->create();

    $response = $this->actingAs($superAdmin)
        ->get(route('admin.venues.show', $venue));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/venues/Show')
        ->has('courts', 1)
    );
});

test('venue profile page includes bookings scoped to venue', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $venue = Venue::factory()->create();
    $court = Court::factory()->for($venue)->create();

    Booking::factory()->for($court)->count(3)->create();

    $otherVenue = Venue::factory()->create();
    $otherCourt = Court::factory()->for($otherVenue)->create();
    Booking::factory()->for($otherCourt)->count(2)->create();

    $response = $this->actingAs($superAdmin)
        ->get(route('admin.venues.show', $venue));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/venues/Show')
        ->has('bookings.data', 3)
    );
});

test('regular admin cannot view venue profile they are not assigned to', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);

    $venue = Venue::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.venues.show', $venue));

    $response->assertForbidden();
});

test('admin assigned to venue can view their venue profile', function () {
    $venue = Venue::factory()->create();
    $admin = User::factory()->create(['venue_id' => $venue->id]);
    $admin->assignRole(RoleName::Admin->value);

    $response = $this->actingAs($admin)
        ->get(route('admin.venues.show', $venue));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/venues/Show')
        ->where('venue.id', $venue->id)
    );
});

test('unauthenticated user cannot access venue profile', function () {
    $venue = Venue::factory()->create();

    $response = $this->get(route('admin.venues.show', $venue));

    $response->assertRedirect('/login');
});
