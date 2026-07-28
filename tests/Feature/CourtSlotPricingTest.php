<?php

use App\Enums\RoleName;
use App\Models\Court;
use App\Models\User;
use App\Models\Venue;

test('super admin can set custom slot prices on a court', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $venue = Venue::factory()->create();

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.courts.store'), [
            'venue_id' => $venue->id,
            'name' => 'Prime Pickleball Court',
            'sport_type' => 'pickleball',
            'status' => 'available',
            'base_price' => 150.00,
            'slot_prices' => [
                '06:00 PM' => 250.00,
                '07:00 PM' => 300.00,
            ],
            'slot_duration_minutes' => 60,
            'buffer_minutes' => 0,
            'is_active' => true,
        ]);

    $response->assertRedirect(route('admin.courts.index'));

    $court = Court::where('name', 'Prime Pickleball Court')->first();
    expect($court)->not->toBeNull();
    expect($court->getSlotPrice('08:00 AM'))->toEqual(150.00); // Fallback to base_price
    expect($court->getSlotPrice('06:00 PM'))->toEqual(250.00); // Custom override
    expect($court->getSlotPrice('07:00 PM'))->toEqual(300.00); // Custom override
});

test('booking store calculates total price using dynamic slot rates', function () {
    $court = Court::factory()->create([
        'base_price' => 100.00,
        'slot_prices' => [
            '06:00 PM' => 200.00,
            '07:00 PM' => 250.00,
        ],
    ]);

    $user = User::factory()->create();
    $user->assignRole(RoleName::Customer->value);

    // Book 3 slots: 10:00 AM (100) + 06:00 PM (200) + 07:00 PM (250) = 550
    $response = $this->actingAs($user)
        ->post('/bookings', [
            'court_id' => $court->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '09171234567',
            'date' => '2026-08-10',
            'time' => ['10:00 AM', '06:00 PM', '07:00 PM'],
        ]);

    $response->assertCreated();

    $this->assertDatabaseHas('bookings', [
        'court_id' => $court->id,
        'date' => '2026-08-10',
        'total_price' => 550.00,
    ]);
});
