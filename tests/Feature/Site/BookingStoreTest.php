<?php

use App\Models\Booking;
use App\Models\Court;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

test('no confirmation email is sent to the customer immediately upon booking submission', function (): void {
    Mail::fake();
    $court = Court::factory()->create();

    $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Alice Player',
        'email' => 'alice@example.com',
        'phone' => '09171234567',
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => ['08:00 AM'],
    ])->assertStatus(201);

    Mail::assertNothingSent();
});

test('a guest can successfully book a court and save it to the database', function (): void {
    $court = Court::factory()->create([
        'base_price' => 50.00,
        'slot_duration_minutes' => 60,
    ]);

    // Use create() to avoid requiring PHP's GD extension
    $file = UploadedFile::fake()->create('receipt.png', 100, 'image/png');

    $response = $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Alice Player',
        'email' => 'alice@example.com',
        'phone' => '09171234567',
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => ['08:00 AM', '09:00 AM'],
        'notes' => 'Looking forward to it!',
        'transaction_code' => 'GC-1234567890',
        'receipt' => $file,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'booking' => [
                'id',
                'reference_code',
                'name',
                'date',
                'time_slots',
                'total_price',
                'receipt_url',
                'qr_code',
            ],
        ]);

    expect($response->json('booking.qr_code'))->toStartWith('data:image/svg+xml;base64,');

    $bookingId = $response->json('booking.id');

    $this->assertDatabaseHas('bookings', [
        'id' => $bookingId,
        'court_id' => $court->id,
        'name' => 'Alice Player',
        'email' => 'alice@example.com',
        'phone' => '09171234567',
        'date' => now()->addDay()->format('Y-m-d'),
        'notes' => 'Looking forward to it!',
        'total_price' => 100.00, // 2 slots * ₱50.00
        'transaction_code' => 'GC-1234567890',
    ]);

    $booking = Booking::find($bookingId);
    expect($booking->time_slots)->toBe(['08:00 AM', '09:00 AM']);
    Storage::disk('public')->assertExists($booking->receipt_path);
});

test('booking succeeds without a receipt file since payment proof is optional', function (): void {
    Storage::fake('public');
    $court = Court::factory()->create();

    $response = $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Alice Player',
        'email' => 'alice@example.com',
        'phone' => '09171234567',
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => ['08:00 AM'],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('booking.receipt_url', null);

    $booking = Booking::query()->latest('id')->first();
    expect($booking->receipt_path)->toBeNull();
});

test('booking validation fails if double-booking a slot is attempted', function (): void {
    $court = Court::factory()->create();
    $date = now()->addDay()->format('Y-m-d');

    // Create an existing booking
    Booking::factory()->create([
        'court_id' => $court->id,
        'date' => $date,
        'time_slots' => ['09:00 AM', '10:00 AM'],
        'status' => 'confirmed',
    ]);

    $file = UploadedFile::fake()->create('receipt2.png', 100, 'image/png');

    // Attempt to book an overlapping slot
    $response = $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Bob Booker',
        'email' => 'bob@example.com',
        'phone' => '09177654321',
        'date' => $date,
        'time' => ['10:00 AM', '11:00 AM'],
        'receipt' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['time']);
});

test('booking validation fails if date is in the past', function (): void {
    $court = Court::factory()->create();
    $file = UploadedFile::fake()->create('receipt3.png', 100, 'image/png');

    $response = $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Old Booking',
        'email' => 'old@example.com',
        'phone' => '09171112222',
        'date' => now()->subDay()->format('Y-m-d'),
        'time' => ['08:00 AM'],
        'receipt' => $file,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['date']);
});

test('booking validation fails if time slot has already passed today', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-07 15:30:00'));
    $court = Court::factory()->create();

    // 03:00 PM slot (start time 15:00) has already passed when current time is 15:30
    $responsePassed = $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Late Customer',
        'email' => 'late@example.com',
        'phone' => '09171112222',
        'date' => '2026-10-07',
        'time' => ['03:00 PM'],
    ]);

    $responsePassed->assertStatus(422)
        ->assertJsonValidationErrors(['time']);

    // 04:00 PM slot has not yet passed and can be booked
    $responseFuture = $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Future Customer',
        'email' => 'future@example.com',
        'phone' => '09171112222',
        'date' => '2026-10-07',
        'time' => ['04:00 PM'],
    ]);

    $responseFuture->assertStatus(201);

    Carbon::setTestNow();
});

test('late night operating slots from 12:00 AM to 02:00 AM remain available during night operating hours', function (): void {
    // Current time is 10:30 PM on Oct 7
    Carbon::setTestNow(Carbon::parse('2026-10-07 22:30:00'));
    $court = Court::factory()->create();

    // 10:00 PM has passed
    $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Past Customer',
        'email' => 'past@example.com',
        'phone' => '09171112222',
        'date' => '2026-10-07',
        'time' => ['10:00 PM'],
    ])->assertStatus(422)->assertJsonValidationErrors(['time']);

    // 11:00 PM, 12:00 AM, 01:00 AM, 02:00 AM are all upcoming tonight and can be booked
    $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Late Night Customer',
        'email' => 'latenight@example.com',
        'phone' => '09171112222',
        'date' => '2026-10-07',
        'time' => ['11:00 PM', '12:00 AM', '01:00 AM', '02:00 AM'],
    ])->assertStatus(201);

    Carbon::setTestNow();
});

test('late night slot is evaluated against operating day at 01:30 AM', function (): void {
    // Current time is 01:30 AM on Oct 8 (part of Oct 7 operating day shift)
    Carbon::setTestNow(Carbon::parse('2026-10-08 01:30:00'));
    $court = Court::factory()->create();

    // 01:00 AM has already passed
    $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Passed Slot Customer',
        'email' => 'passed@example.com',
        'phone' => '09171112222',
        'date' => '2026-10-07',
        'time' => ['01:00 AM'],
    ])->assertStatus(422)->assertJsonValidationErrors(['time']);

    // 02:00 AM is upcoming and can still be booked
    $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Active Night Customer',
        'email' => 'active@example.com',
        'phone' => '09171112222',
        'date' => '2026-10-07',
        'time' => ['02:00 AM'],
    ])->assertStatus(201);

    Carbon::setTestNow();
});
