<?php

use App\Enums\RoleName;
use App\Models\Venue;

test('super admin can view venues index', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin));

    $this->get(route('admin.venues.index'))->assertOk();
});

test('admin can view venues index', function () {
    $this->actingAs(userWithRole(RoleName::Admin));

    $this->get(route('admin.venues.index'))->assertOk();
});

test('staff and customer cannot view venues index', function (RoleName $role) {
    $this->actingAs(userWithRole($role));

    $this->get(route('admin.venues.index'))->assertForbidden();
})->with([RoleName::Staff, RoleName::Customer]);

test('super admin can create a venue', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin));

    $response = $this->post(route('admin.venues.store'), [
        'name' => 'Sunset Sports Complex',
        'address' => '456 Sunset Blvd',
        'phone' => '555-9999',
        'email' => 'info@sunset.com',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.venues.index'));
    $this->assertDatabaseHas('venues', [
        'name' => 'Sunset Sports Complex',
        'slug' => 'sunset-sports-complex',
    ]);
});

test('admin can create a venue', function () {
    $this->actingAs(userWithRole(RoleName::Admin));

    $response = $this->post(route('admin.venues.store'), [
        'name' => 'Bayside Arena',
        'address' => '789 Harbor Rd',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.venues.index'));
    $this->assertDatabaseHas('venues', [
        'name' => 'Bayside Arena',
        'slug' => 'bayside-arena',
    ]);
});

test('admin can update a venue', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $venue = Venue::create([
        'name' => 'Old Venue Name',
        'slug' => 'old-venue-name',
    ]);

    $response = $this->put(route('admin.venues.update', $venue->id), [
        'name' => 'Updated Venue Name',
        'address' => 'New Address',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.venues.index'));
    expect($venue->fresh()->name)->toBe('Updated Venue Name');
});

test('super admin can delete a venue', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin));
    $venue = Venue::create([
        'name' => 'Temporary Venue',
        'slug' => 'temporary-venue',
    ]);

    $this->delete(route('admin.venues.destroy', $venue->id))
        ->assertRedirect(route('admin.venues.index'));

    $this->assertSoftDeleted('venues', ['id' => $venue->id]);
});

test('admin cannot delete a venue', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $venue = Venue::create([
        'name' => 'Protected Venue',
        'slug' => 'protected-venue',
    ]);

    $this->delete(route('admin.venues.destroy', $venue->id))
        ->assertForbidden();

    $this->assertDatabaseHas('venues', ['id' => $venue->id, 'deleted_at' => null]);
});
