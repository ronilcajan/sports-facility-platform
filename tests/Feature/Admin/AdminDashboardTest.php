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

test('super admin can confirm a booking with optional admin notes', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $booking = Booking::factory()->create(['status' => 'pending']);

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update-status', $booking->id), [
            'status' => 'approved',
            'admin_notes' => 'Court prepared and reserved.',
        ])
        ->assertRedirect();

    $fresh = $booking->fresh();
    expect($fresh->status)->toBe('approved')
        ->and($fresh->admin_notes)->toBe('Court prepared and reserved.');
});

test('super admin cannot reject a booking without admin notes', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $booking = Booking::factory()->create(['status' => 'pending']);

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update-status', $booking->id), [
            'status' => 'rejected',
            'admin_notes' => '',
        ])
        ->assertSessionHasErrors(['admin_notes']);

    expect($booking->fresh()->status)->toBe('pending');
});

test('super admin can reject a booking when providing admin notes', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $booking = Booking::factory()->create(['status' => 'pending']);

    $this->actingAs($superAdmin)
        ->patch(route('admin.bookings.update-status', $booking->id), [
            'status' => 'rejected',
            'admin_notes' => 'Court undergoing floor maintenance on this date.',
        ])
        ->assertRedirect();

    $fresh = $booking->fresh();
    expect($fresh->status)->toBe('rejected')
        ->and($fresh->admin_notes)->toBe('Court undergoing floor maintenance on this date.');
});

test('booking model calculates total hours correctly for single slots, multiple slots, and ranges', function () {
    $court = Court::factory()->create(['slot_duration_minutes' => 60]);

    $booking1 = Booking::factory()->for($court)->make([
        'time_slots' => ['07:00 AM'],
    ]);
    expect($booking1->total_hours)->toBe(1.0);

    $booking2 = Booking::factory()->for($court)->make([
        'time_slots' => ['09:00 AM', '10:00 AM'],
    ]);
    expect($booking2->total_hours)->toBe(2.0);

    $booking3 = Booking::factory()->for($court)->make([
        'time_slots' => ['9:00 AM – 11:00 AM'],
    ]);
    expect($booking3->total_hours)->toBe(2.0);

    $booking4 = Booking::factory()->for($court)->make([
        'time_slots' => ['7:00 AM – 8:00 AM', '9:00 AM – 11:00 AM'],
    ]);
    expect($booking4->total_hours)->toBe(3.0);
});

test('super admin table view includes total_hours in tableBookings', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);
    $court = Court::factory()->create(['slot_duration_minutes' => 60]);

    Booking::factory()->for($court)->create([
        'date' => now()->toDateString(),
        'time_slots' => ['07:00 AM'],
        'status' => 'approved',
    ]);

    $response = $this->actingAs($superAdmin)
        ->get('/admin/bookings?view=table');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/bookings/Index')
            ->has('tableBookings')
            ->where('tableBookings.0.total_hours', fn ($val) => (float) $val === 1.0)
        );
});
