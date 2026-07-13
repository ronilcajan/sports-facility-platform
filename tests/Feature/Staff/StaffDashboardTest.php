<?php

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;

test('court staff can access staff dashboard for assigned court', function () {
    $staff = User::factory()->create();
    $staff->assignRole(RoleName::Staff->value);

    $court1 = Court::factory()->create();
    $court2 = Court::factory()->create();

    $staff->assignedCourts()->attach($court1);

    Booking::factory()->create(['court_id' => $court1->id, 'name' => 'Assigned Booking']);
    Booking::factory()->create(['court_id' => $court2->id, 'name' => 'Unassigned Booking']);

    $this->actingAs($staff)
        ->get(route('staff.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('staff/Dashboard')
            ->where('selectedCourt.id', $court1->id)
        );
});

test('court staff can approve booking for assigned court', function () {
    $staff = User::factory()->create();
    $staff->assignRole(RoleName::Staff->value);

    $court = Court::factory()->create();
    $staff->assignedCourts()->attach($court);

    $booking = Booking::factory()->create(['court_id' => $court->id, 'status' => 'pending']);

    $this->actingAs($staff)
        ->patch(route('staff.bookings.update-status', $booking->id), [
            'status' => 'approved',
        ])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('approved');
});

test('court staff cannot view or update booking for unassigned court', function () {
    $staff = User::factory()->create();
    $staff->assignRole(RoleName::Staff->value);

    $court1 = Court::factory()->create();
    $court2 = Court::factory()->create();

    $staff->assignedCourts()->attach($court1);

    $unassignedBooking = Booking::factory()->create(['court_id' => $court2->id, 'status' => 'pending']);

    $this->actingAs($staff)
        ->patch(route('staff.bookings.update-status', $unassignedBooking->id), [
            'status' => 'approved',
        ])
        ->assertForbidden();
});

test('court staff can create blackout schedule entry for assigned court', function () {
    $staff = User::factory()->create();
    $staff->assignRole(RoleName::Staff->value);

    $court = Court::factory()->create();
    $staff->assignedCourts()->attach($court);

    $this->actingAs($staff)
        ->post(route('staff.schedules.store'), [
            'court_id' => $court->id,
            'date' => '2026-08-01',
            'all_day' => true,
            'reason' => 'Annual Maintenance',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('court_unavailabilities', [
        'court_id' => $court->id,
        'date' => '2026-08-01',
        'reason' => 'Annual Maintenance',
    ]);
});
