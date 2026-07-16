<?php

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;

/**
 * Create an admin scoped to the given venue.
 */
function venueAdmin(Venue $venue): User
{
    $admin = userWithRole(RoleName::Admin);
    $admin->update(['venue_id' => $venue->id]);

    return $admin->fresh();
}

test('venue admin bookings index only lists their venue bookings', function () {
    $venue = Venue::factory()->create();
    $admin = venueAdmin($venue);

    $ownBooking = Booking::factory()->for(Court::factory()->for($venue))->create();
    Booking::factory()->for(Court::factory()->for(Venue::factory()->create()))->create();

    $this->actingAs($admin)
        ->get(route('admin.bookings.index', ['view' => 'list']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/bookings/Index')
            ->has('bookings.data', 1)
            ->where('bookings.data.0.id', $ownBooking->id));
});

test('super admin bookings index lists every venue booking', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);

    Booking::factory()->for(Court::factory()->for(Venue::factory()->create()))->create();
    Booking::factory()->for(Court::factory()->for(Venue::factory()->create()))->create();

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index', ['view' => 'list']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('bookings.data', 2));
});

test('venue admin cannot view a booking from another venue', function () {
    $venue = Venue::factory()->create();
    $admin = venueAdmin($venue);

    $foreignBooking = Booking::factory()
        ->for(Court::factory()->for(Venue::factory()->create()))
        ->create();

    $this->actingAs($admin)
        ->get(route('admin.bookings.show', $foreignBooking->id))
        ->assertForbidden();
});

test('venue admin courts index only lists their venue courts', function () {
    $venue = Venue::factory()->create();
    $admin = venueAdmin($venue);

    Court::factory()->for($venue)->count(2)->create();
    Court::factory()->for(Venue::factory()->create())->create();

    $this->actingAs($admin)
        ->get(route('admin.courts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/courts/Index')
            ->has('courts', 2));
});

test('venue admin cannot open the edit page of another venue court', function () {
    $venue = Venue::factory()->create();
    $admin = venueAdmin($venue);

    $foreignCourt = Court::factory()->for(Venue::factory()->create())->create();

    $this->actingAs($admin)
        ->get(route('admin.courts.edit', $foreignCourt))
        ->assertForbidden();
});

test('venue admin users list is scoped to their venue staff and customers', function () {
    $venue = Venue::factory()->create();
    $admin = venueAdmin($venue);

    $venueStaff = userWithRole(RoleName::Staff);
    $venueStaff->update(['venue_id' => $venue->id]);

    $otherStaff = userWithRole(RoleName::Staff);
    $otherStaff->update(['venue_id' => Venue::factory()->create()->id]);

    $customer = userWithRole(RoleName::Customer);
    Booking::factory()->for(Court::factory()->for($venue))->create(['user_id' => $customer->id]);

    $otherCustomer = userWithRole(RoleName::Customer);
    Booking::factory()->create(['user_id' => $otherCustomer->id]);

    // Visible: the admin (venue_id), the venue staff (venue_id), the customer (booked here) = 3.
    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/Index')
            ->has('users.data', 3));
});

test('staff created by a venue admin are assigned to that venue', function () {
    $venue = Venue::factory()->create();
    $admin = venueAdmin($venue);

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New Staffer',
        'email' => 'staffer@example.com',
        'password' => 'password123',
        'role' => RoleName::Staff->value,
    ])->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'staffer@example.com',
        'venue_id' => $venue->id,
    ]);
});

test('venue admin settings page loads their own venue', function () {
    $venue = Venue::factory()->create();
    $admin = venueAdmin($venue);

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/settings/Index')
            ->where('venue.id', $venue->id));
});

test('venue admin can update their own venue via settings', function () {
    $venue = Venue::factory()->create(['name' => 'Before']);
    $admin = venueAdmin($venue);

    $this->actingAs($admin)->put(route('admin.settings.update'), [
        'name' => 'After',
        'is_active' => true,
    ])->assertRedirect(route('admin.settings.edit'));

    expect($venue->fresh()->name)->toBe('After');
});

test('settings page 404s for a user without an assigned venue', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);

    $this->actingAs($superAdmin)
        ->get(route('admin.settings.edit'))
        ->assertNotFound();
});

test('customer bookings page lists only their own bookings', function () {
    $customer = userWithRole(RoleName::Customer);

    Booking::factory()->create(['user_id' => $customer->id]);
    Booking::factory()->create();

    $this->actingAs($customer)
        ->get(route('customer.bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('customer/Bookings')
            ->has('bookings', 1));
});
