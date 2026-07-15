<?php

use App\Enums\RoleName;
use App\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

test('admin can save venue payment methods with an optional QR upload', function () {
    Storage::fake('public');
    $this->actingAs(userWithRole(RoleName::Admin));

    $qr = UploadedFile::fake()->create('gcash-qr.png', 100, 'image/png');

    $response = $this->post(route('admin.venues.store'), [
        'name' => 'Pay Ready Venue',
        'gcash_number' => '0917 123 4567',
        'gcash_qr' => $qr,
        'maya_number' => '0918 555 0000',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.venues.index'));

    $venue = Venue::query()->where('slug', 'pay-ready-venue')->firstOrFail();
    expect($venue->gcash_number)->toBe('0917 123 4567');
    expect($venue->maya_number)->toBe('0918 555 0000');
    expect($venue->gcash_qr_path)->not->toBeNull();
    Storage::disk('public')->assertExists($venue->gcash_qr_path);
});

test('venue payment methods only include configured methods', function () {
    $venue = Venue::create([
        'name' => 'Partial Pay Venue',
        'slug' => 'partial-pay-venue',
        'gcash_number' => '0917 123 4567',
    ]);

    $methods = $venue->paymentMethods();

    expect($methods)->toHaveKey('gcash');
    expect($methods)->not->toHaveKey('maya');
    expect($methods['gcash']['number'])->toBe('0917 123 4567');
    expect($methods['gcash']['qr_url'])->toBeNull();
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
