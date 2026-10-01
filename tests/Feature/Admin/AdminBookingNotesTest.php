<?php

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Venue;
use Illuminate\Support\Carbon;

function bookingForNotes(array $attributes = []): Booking
{
    return Booking::factory()
        ->for(Court::factory()->for(Venue::factory()->create()))
        ->create(array_merge([
            'date' => Carbon::now()->toDateString(),
            'status' => 'confirmed',
        ], $attributes));
}

test('admin can add staff notes to a booking', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $booking = bookingForNotes();

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update', $booking), [
            'name' => $booking->name,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'admin_notes' => 'Paid the balance in cash at the counter.',
        ])
        ->assertRedirect();

    expect($booking->fresh()->admin_notes)->toBe('Paid the balance in cash at the counter.');
});

test('saving staff notes leaves the customer notes untouched', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $booking = bookingForNotes(['notes' => 'Please reserve the far court.']);

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update', $booking), [
            'name' => $booking->name,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'notes' => 'Please reserve the far court.',
            'admin_notes' => 'Regular customer, allow late check-in.',
        ]);

    $fresh = $booking->fresh();
    expect($fresh->notes)->toBe('Please reserve the far court.')
        ->and($fresh->admin_notes)->toBe('Regular customer, allow late check-in.');
});

test('an update that omits staff notes keeps the existing ones', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $booking = bookingForNotes(['admin_notes' => 'Deposit still outstanding.']);

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update', $booking), [
            'name' => $booking->name,
            'email' => $booking->email,
            'phone' => $booking->phone,
        ]);

    expect($booking->fresh()->admin_notes)->toBe('Deposit still outstanding.');
});

test('staff notes are rejected when longer than the column allows', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $booking = bookingForNotes();

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update', $booking), [
            'name' => $booking->name,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'admin_notes' => str_repeat('a', 2001),
        ])
        ->assertSessionHasErrors('admin_notes');
});

test('the bookings table payload carries slot duration so booked hours can be totalled', function () {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $court = Court::factory()
        ->for(Venue::factory()->create())
        ->create(['slot_duration_minutes' => 30]);

    Booking::factory()->for($court)->create([
        'date' => Carbon::now()->toDateString(),
        'status' => 'confirmed',
        'time_slots' => ['07:00 AM', '08:00 AM'],
    ]);

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('tableBookings.0.court.slot_duration_minutes', 30)
            ->has('tableBookings.0.time_slots', 2));
});
