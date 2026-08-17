<?php

use App\Models\Booking;
use App\Models\Court;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    Mail::fake();
});

/**
 * Statuses that must release the slot back to the public calendar.
 *
 * @return array<int, string>
 */
dataset('released statuses', ['rejected', 'cancelled']);

/**
 * Statuses that must keep holding the slot.
 *
 * @return array<int, string>
 */
dataset('holding statuses', ['pending', 'approved', 'confirmed', 'completed']);

test('a booking that is no longer live frees its slot on the availability endpoint', function (string $status): void {
    $court = Court::factory()->create();
    $date = now()->addDay()->format('Y-m-d');

    Booking::factory()->create([
        'court_id' => $court->id,
        'date' => $date,
        'time_slots' => ['08:00 AM'],
        'status' => $status,
    ]);

    $this->getJson(route('site.bookings.availability', ['date' => $date, 'court_id' => $court->id]))
        ->assertOk()
        ->assertJsonPath("booked_slots.{$court->id}", null);
})->with('released statuses');

test('a live booking still holds its slot on the availability endpoint', function (string $status): void {
    $court = Court::factory()->create();
    $date = now()->addDay()->format('Y-m-d');

    Booking::factory()->create([
        'court_id' => $court->id,
        'date' => $date,
        'time_slots' => ['08:00 AM'],
        'status' => $status,
    ]);

    $this->getJson(route('site.bookings.availability', ['date' => $date, 'court_id' => $court->id]))
        ->assertOk()
        ->assertJsonPath("booked_slots.{$court->id}", ['08:00 AM']);
})->with('holding statuses');

test('a slot held by a rejected booking can be booked again by a customer', function (): void {
    $court = Court::factory()->create();
    $date = now()->addDay()->format('Y-m-d');

    Booking::factory()->create([
        'court_id' => $court->id,
        'date' => $date,
        'time_slots' => ['08:00 AM'],
        'status' => 'rejected',
    ]);

    $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Alice Player',
        'email' => 'alice@example.com',
        'phone' => '09171234567',
        'date' => $date,
        'time' => ['08:00 AM'],
    ])->assertStatus(201);

    expect(Booking::where('court_id', $court->id)->where('status', '!=', 'rejected')->count())->toBe(1);
});
