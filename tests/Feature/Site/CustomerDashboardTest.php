<?php

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use Inertia\Testing\AssertableInertia as Assert;

test('customer dashboard shows guest bookings made with the same email', function (): void {
    $user = userWithRole(RoleName::Customer);
    $user->update(['email' => 'player@example.com']);

    $court = Court::factory()->create();

    // Guest booking (no user_id) made with the customer's email — should appear
    Booking::factory()->create([
        'court_id' => $court->id,
        'user_id' => null,
        'email' => 'player@example.com',
    ]);

    // Booking linked directly by user_id — should appear
    Booking::factory()->create([
        'court_id' => $court->id,
        'user_id' => $user->id,
        'email' => 'somethingelse@example.com',
    ]);

    // Unrelated booking (different email, no user_id) — should NOT appear
    Booking::factory()->create([
        'court_id' => $court->id,
        'user_id' => null,
        'email' => 'stranger@example.com',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('bookings', 2)
        );
});
