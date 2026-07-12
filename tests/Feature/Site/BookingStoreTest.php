<?php

use App\Models\Booking;
use App\Models\Court;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
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
            ],
        ]);

    $bookingId = $response->json('booking.id');

    $this->assertDatabaseHas('bookings', [
        'id' => $bookingId,
        'court_id' => $court->id,
        'name' => 'Alice Player',
        'email' => 'alice@example.com',
        'phone' => '09171234567',
        'date' => now()->addDay()->format('Y-m-d'),
        'notes' => 'Looking forward to it!',
        'total_price' => 100.00, // 2 slots * $50.00
    ]);

    $booking = Booking::find($bookingId);
    expect($booking->time_slots)->toBe(['08:00 AM', '09:00 AM']);
    Storage::disk('public')->assertExists($booking->receipt_path);
});

test('booking validation fails if receipt file is missing', function (): void {
    $court = Court::factory()->create();

    $response = $this->postJson(route('site.bookings.store'), [
        'court_id' => $court->id,
        'name' => 'Alice Player',
        'email' => 'alice@example.com',
        'phone' => '09171234567',
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => ['08:00 AM'],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['receipt']);
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
