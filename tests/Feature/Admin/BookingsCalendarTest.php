<?php

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Venue;
use Illuminate\Support\Carbon;

test('admin bookings calendar returns a five-day window from today by default', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $today = Carbon::now()->toDateString();

    Booking::factory()
        ->for(Court::factory()->for(Venue::factory()->create()))
        ->create(['date' => $today, 'status' => 'confirmed']);

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('view', 'calendar')
            ->has('days', 5)
            ->where('days.0.date', $today)
            ->has('days.0.bookings', 1)
            ->where('window.start', $today));
});

test('the start param shifts the calendar window and anchors', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $start = Carbon::now()->addDays(10)->toDateString();

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['start' => $start]))
        ->assertInertia(fn ($page) => $page
            ->where('days.0.date', $start)
            ->where('window.start', $start)
            ->where('window.prev', Carbon::parse($start)->subDays(5)->toDateString())
            ->where('window.next', Carbon::parse($start)->addDays(5)->toDateString()));
});

test('venue admin calendar only includes their own venue bookings', function () {
    $venue = Venue::factory()->create();
    $admin = userWithRole(RoleName::Admin);
    $admin->update(['venue_id' => $venue->id]);
    $today = Carbon::now()->toDateString();

    Booking::factory()->for(Court::factory()->for($venue))->create(['date' => $today, 'status' => 'confirmed']);
    Booking::factory()->for(Court::factory()->for(Venue::factory()->create()))->create(['date' => $today, 'status' => 'confirmed']);

    $this->actingAs($admin)
        ->get(route('admin.bookings.index'))
        ->assertInertia(fn ($page) => $page->has('days.0.bookings', 1));
});

test('the calendar hides rejected and cancelled bookings by default', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $today = Carbon::now()->toDateString();
    $court = Court::factory()->for(Venue::factory()->create())->create();

    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'confirmed']);
    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'cancelled']);

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index'))
        ->assertInertia(fn ($page) => $page->has('days.0.bookings', 1));

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['status' => 'cancelled']))
        ->assertInertia(fn ($page) => $page
            ->has('days.0.bookings', 1)
            ->where('days.0.bookings.0.status', 'cancelled'));
});

test('super admin can filter the calendar by venue', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $venueA = Venue::factory()->create();
    $venueB = Venue::factory()->create();
    $today = Carbon::now()->toDateString();

    Booking::factory()->for(Court::factory()->for($venueA))->create(['date' => $today, 'status' => 'confirmed']);
    Booking::factory()->for(Court::factory()->for($venueB))->create(['date' => $today, 'status' => 'confirmed']);

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['venue_id' => $venueA->id]))
        ->assertInertia(fn ($page) => $page->has('days.0.bookings', 1));
});

test('bookings list view still returns paginated data', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    Booking::factory()->for(Court::factory()->for(Venue::factory()->create()))->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['view' => 'list']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('view', 'list')
            ->has('bookings.data'));
});

test('staff calendar only shows bookings for their assigned courts', function () {
    $staff = userWithRole(RoleName::Staff);
    $assigned = Court::factory()->for(Venue::factory()->create())->create();
    $staff->assignedCourts()->attach($assigned);
    $other = Court::factory()->for(Venue::factory()->create())->create();
    $today = Carbon::now()->toDateString();

    Booking::factory()->for($assigned)->create(['date' => $today, 'status' => 'confirmed']);
    Booking::factory()->for($other)->create(['date' => $today, 'status' => 'confirmed']);

    $this->actingAs($staff)
        ->get(route('staff.bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('view', 'calendar')
            ->has('days.0.bookings', 1));
});
