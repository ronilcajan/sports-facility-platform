<?php

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Venue;
use Illuminate\Support\Carbon;

test('admin bookings page defaults to table view', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $today = Carbon::now()->toDateString();

    Booking::factory()
        ->for(Court::factory()->for(Venue::factory()->create()))
        ->create(['date' => $today, 'status' => 'confirmed']);

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('view', 'table')
            ->has('tableDates', 7)
            ->has('tableBookings', 1));
});

test('admin bookings calendar view returns a five-day window when view is calendar', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $today = Carbon::now()->toDateString();

    Booking::factory()
        ->for(Court::factory()->for(Venue::factory()->create()))
        ->create(['date' => $today, 'status' => 'confirmed']);

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['view' => 'calendar']))
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
        ->get(route('admin.bookings.index', ['view' => 'calendar', 'start' => $start]))
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
        ->get(route('admin.bookings.index', ['view' => 'calendar']))
        ->assertInertia(fn ($page) => $page->has('days.0.bookings', 1));
});

test('the calendar shows every status so staff can see rejections', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $today = Carbon::now()->toDateString();
    $court = Court::factory()->for(Venue::factory()->create())->create();

    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'confirmed']);
    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'pending']);
    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'rejected']);
    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'cancelled']);

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['view' => 'calendar']))
        ->assertInertia(fn ($page) => $page->has('days.0.bookings', 4));

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['view' => 'calendar', 'status' => 'cancelled']))
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
        ->get(route('admin.bookings.index', ['view' => 'calendar', 'venue_id' => $venueA->id]))
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

test('an admin can manually create a booking with a computed price', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $court = Court::factory()->for(Venue::factory()->create())->create(['base_price' => 30]);

    $this->actingAs($superAdmin)
        ->post(route('admin.bookings.store'), [
            'court_id' => $court->id,
            'name' => 'Walk In',
            'email' => 'walkin@example.com',
            'phone' => '09170000000',
            'date' => Carbon::now()->toDateString(),
            'time_slots' => ['08:00 AM', '09:00 AM'],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('bookings', [
        'court_id' => $court->id,
        'email' => 'walkin@example.com',
        'status' => 'approved',
    ]);

    expect(Booking::where('email', 'walkin@example.com')->first()->total_price)->toBe('60.00');
});

test('a venue admin cannot manually create a booking for another venue court', function () {
    $venue = Venue::factory()->create();
    $admin = userWithRole(RoleName::Admin);
    $admin->update(['venue_id' => $venue->id]);

    $foreignCourt = Court::factory()->for(Venue::factory()->create())->create();

    $this->actingAs($admin)
        ->post(route('admin.bookings.store'), [
            'court_id' => $foreignCourt->id,
            'name' => 'Blocked',
            'email' => 'blocked@example.com',
            'phone' => '09170000000',
            'date' => Carbon::now()->toDateString(),
            'time_slots' => ['08:00 AM'],
        ])
        ->assertNotFound();

    $this->assertDatabaseMissing('bookings', ['email' => 'blocked@example.com']);
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

test('the table board shows every status, matching the calendar', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $today = Carbon::now()->toDateString();
    $court = Court::factory()->for(Venue::factory()->create())->create();

    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'confirmed']);
    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'cancelled']);
    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'rejected']);

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['view' => 'table']))
        ->assertInertia(fn ($page) => $page->has('tableBookings', 3));

    // An explicit status filter still surfaces them on demand.
    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['view' => 'table', 'status' => 'rejected']))
        ->assertInertia(fn ($page) => $page
            ->has('tableBookings', 1)
            ->where('tableBookings.0.status', 'rejected'));
});

test('the list view still shows every booking as the full record log', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $today = Carbon::now()->toDateString();
    $court = Court::factory()->for(Venue::factory()->create())->create();

    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'confirmed']);
    Booking::factory()->for($court)->create(['date' => $today, 'status' => 'rejected']);

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['view' => 'list']))
        ->assertInertia(fn ($page) => $page->has('bookings.data', 2));
});

test('a slot left by a rejected booking stays visible on the board and still accepts a new booking', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $today = Carbon::now()->toDateString();
    $court = Court::factory()->for(Venue::factory()->create())->create();

    Booking::factory()->for($court)->create([
        'date' => $today,
        'time_slots' => ['08:00 AM'],
        'status' => 'rejected',
    ]);

    // The rejection stays on the board so staff can see what happened...
    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['view' => 'calendar']))
        ->assertInertia(fn ($page) => $page
            ->has('days.0.bookings', 1)
            ->where('days.0.bookings.0.status', 'rejected'));

    // ...and the hour it used to hold can be booked again.
    $this->actingAs($superAdmin)
        ->post(route('admin.bookings.store'), [
            'court_id' => $court->id,
            'name' => 'Walk In',
            'email' => 'walkin@example.com',
            'phone' => '09171234567',
            'date' => $today,
            'time_slots' => ['08:00 AM'],
        ])
        ->assertRedirect();

    expect(Booking::where('court_id', $court->id)->where('status', '!=', 'rejected')->count())->toBe(1);
});

test('an admin cannot manually create a booking with a past date', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $court = Court::factory()->for(Venue::factory()->create())->create(['base_price' => 30]);

    $this->actingAs($superAdmin)
        ->post(route('admin.bookings.store'), [
            'court_id' => $court->id,
            'name' => 'Past Booking',
            'email' => 'past@example.com',
            'phone' => '09170000000',
            'date' => Carbon::now()->subDay()->toDateString(),
            'time_slots' => ['08:00 AM'],
        ])
        ->assertSessionHasErrors(['date']);

    $this->assertDatabaseMissing('bookings', ['email' => 'past@example.com']);
});

test('an admin cannot update a booking date to a past date', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $court = Court::factory()->for(Venue::factory()->create())->create();
    $booking = Booking::factory()->for($court)->create([
        'date' => Carbon::now()->addDay()->toDateString(),
        'time_slots' => ['08:00 AM'],
    ]);

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update', $booking->id), [
            'name' => $booking->name,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'date' => Carbon::now()->subDays(2)->toDateString(),
            'time_slots' => ['08:00 AM'],
        ])
        ->assertSessionHasErrors(['date']);

    expect($booking->fresh()->date->toDateString())->toBe(Carbon::now()->addDay()->toDateString());
});
