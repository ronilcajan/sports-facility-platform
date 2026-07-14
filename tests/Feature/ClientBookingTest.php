<?php

use App\Enums\RoleName;
use App\Models\Booking;
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
