<?php

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;

test('super admin can access super admin dashboard and view system stats', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $court = Court::factory()->create();
    Booking::factory()->create(['court_id' => $court->id, 'status' => 'confirmed', 'total_price' => 150.00]);

    $this->actingAs($superAdmin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/Dashboard')
            ->has('stats')
            ->has('courtsSummary')
            ->has('recentBookings')
        );
});

test('staff user cannot access super admin dashboard', function () {
    $staff = User::factory()->create();
    $staff->assignRole(RoleName::Staff->value);

    $this->actingAs($staff)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('super admin can view global bookings list and update status', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $booking = Booking::factory()->create(['status' => 'pending']);

    $this->actingAs($superAdmin)
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/bookings/Index'));

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update-status', $booking->id), [
            'status' => 'approved',
        ])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe('approved');
});
