<?php

use App\Enums\RoleName;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Court;
use App\Models\Venue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
});

test('admin approving a booking sends a confirmation email to the customer', function (): void {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $court = Court::factory()->for(Venue::factory()->create())->create();
    $booking = Booking::factory()->for($court)->create([
        'email' => 'customer@example.com',
        'status' => 'pending',
        'date' => Carbon::now()->addDay()->toDateString(),
    ]);

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update-status', $booking), [
            'status' => 'approved',
        ])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('approved');

    Mail::assertSent(BookingConfirmedMail::class, function (BookingConfirmedMail $mail) {
        return $mail->hasTo('customer@example.com')
            && str_contains($mail->envelope()->subject, 'Booking Confirmed');
    });
});

test('admin rejecting a booking does not send a confirmation email', function (): void {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $court = Court::factory()->for(Venue::factory()->create())->create();
    $booking = Booking::factory()->for($court)->create([
        'email' => 'customer@example.com',
        'status' => 'pending',
        'date' => Carbon::now()->addDay()->toDateString(),
    ]);

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update-status', $booking), [
            'status' => 'rejected',
            'notes' => 'Court unavailable due to maintenance.',
        ])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('rejected');

    Mail::assertNothingSent();
});

test('staff assigned to court approving a booking sends a confirmation email', function (): void {
    $staff = userWithRole(RoleName::Staff);
    $court = Court::factory()->for(Venue::factory()->create())->create();
    $court->staff()->attach($staff->id);

    $booking = Booking::factory()->for($court)->create([
        'email' => 'customer@example.com',
        'status' => 'pending',
        'date' => Carbon::now()->addDay()->toDateString(),
    ]);

    $this->actingAs($staff)
        ->patch(route('staff.bookings.update-status', $booking), [
            'status' => 'approved',
        ])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('approved');

    Mail::assertSent(BookingConfirmedMail::class, function (BookingConfirmedMail $mail) {
        return $mail->hasTo('customer@example.com');
    });
});

test('updating an already approved booking does not re-send confirmation email', function (): void {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $court = Court::factory()->for(Venue::factory()->create())->create();
    $booking = Booking::factory()->for($court)->create([
        'email' => 'customer@example.com',
        'status' => 'approved',
        'date' => Carbon::now()->addDay()->toDateString(),
    ]);

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update-status', $booking), [
            'status' => 'approved',
            'notes' => 'Updated notes without changing status',
        ])
        ->assertRedirect();

    Mail::assertNothingSent();
});

test('admin creating a manual approved booking sends a confirmation email', function (): void {
    $superAdmin = userWithRole(RoleName::SuperAdmin);
    $court = Court::factory()->for(Venue::factory()->create())->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.bookings.store'), [
            'court_id' => $court->id,
            'name' => 'Walk In Player',
            'email' => 'walkin@example.com',
            'phone' => '09170001111',
            'date' => Carbon::now()->addDays(2)->toDateString(),
            'time_slots' => ['08:00 AM'],
        ])
        ->assertRedirect();

    Mail::assertSent(BookingConfirmedMail::class, function (BookingConfirmedMail $mail) {
        return $mail->hasTo('walkin@example.com');
    });
});
