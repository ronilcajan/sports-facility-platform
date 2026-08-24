<?php

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;

test('customer can update their own booking details', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleName::Customer->value);

    $booking = Booking::factory()->create([
        'user_id' => $customer->id,
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'phone' => '1234567890',
    ]);

    $this->actingAs($customer)
        ->patch(route('site.bookings.update', $booking->id), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '0987654321',
            'notes' => 'Some notes',
        ])
        ->assertOk();

    expect($booking->fresh())
        ->name->toBe('Updated Name')
        ->email->toBe('updated@example.com');
});

test('customer can update their booking court date and time slots', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleName::Customer->value);

    $court1 = Court::factory()->create(['base_price' => 100]);
    $court2 = Court::factory()->create(['base_price' => 150]);

    $futureDate1 = now()->addDays(2)->toDateString();
    $futureDate2 = now()->addDays(3)->toDateString();

    $booking = Booking::factory()->create([
        'user_id' => $customer->id,
        'court_id' => $court1->id,
        'date' => $futureDate1,
        'time_slots' => ['08:00 AM'],
        'total_price' => 100,
    ]);

    $this->actingAs($customer)
        ->patch(route('site.bookings.update', $booking->id), [
            'court_id' => $court2->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => '1234567890',
            'date' => $futureDate2,
            'time' => ['09:00 AM', '10:00 AM'],
        ])
        ->assertOk();

    $updated = $booking->fresh();
    expect($updated->court_id)->toBe($court2->id);
    expect($updated->date->toDateString())->toBe($futureDate2);
    expect($updated->time_slots)->toBe(['09:00 AM', '10:00 AM']);
    expect((float) $updated->total_price)->toBe(300.0);
});

test('customer cannot update their booking date to a past date', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleName::Customer->value);

    $court = Court::factory()->create();
    $futureDate = now()->addDays(2)->toDateString();

    $booking = Booking::factory()->create([
        'user_id' => $customer->id,
        'court_id' => $court->id,
        'date' => $futureDate,
        'time_slots' => ['08:00 AM'],
    ]);

    $this->actingAs($customer)
        ->patch(route('site.bookings.update', $booking->id), [
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => '1234567890',
            'date' => now()->subDay()->toDateString(),
            'time' => ['08:00 AM'],
        ])
        ->assertSessionHasErrors(['date']);

    expect($booking->fresh()->date->toDateString())->toBe($futureDate);
});

test('customer cannot update another customers booking details', function () {
    $customer1 = User::factory()->create();
    $customer1->assignRole(RoleName::Customer->value);

    $customer2 = User::factory()->create();
    $customer2->assignRole(RoleName::Customer->value);

    $booking = Booking::factory()->create([
        'user_id' => $customer1->id,
        'name' => 'Original Name',
    ]);

    $this->actingAs($customer2)
        ->patch(route('site.bookings.update', $booking->id), [
            'name' => 'Hacker Name',
            'email' => 'hacker@example.com',
            'phone' => '0000000000',
        ])
        ->assertForbidden();

    expect($booking->fresh()->name)->toBe('Original Name');
});

test('customer can delete/cancel their own booking', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleName::Customer->value);

    $booking = Booking::factory()->create([
        'user_id' => $customer->id,
    ]);

    $this->actingAs($customer)
        ->delete(route('site.bookings.destroy', $booking->id))
        ->assertOk();

    $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
});

test('customer cannot delete another customers booking', function () {
    $customer1 = User::factory()->create();
    $customer1->assignRole(RoleName::Customer->value);

    $customer2 = User::factory()->create();
    $customer2->assignRole(RoleName::Customer->value);

    $booking = Booking::factory()->create([
        'user_id' => $customer1->id,
    ]);

    $this->actingAs($customer2)
        ->delete(route('site.bookings.destroy', $booking->id))
        ->assertForbidden();

    $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
});
