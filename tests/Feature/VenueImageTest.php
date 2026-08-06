<?php

use App\Enums\RoleName;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('super admin can upload venue cover image', function () {
    Storage::fake('public');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $venue = Venue::factory()->create();
    $file = UploadedFile::fake()->create('venue_cover.png', 100, 'image/png');

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.venues.update', $venue->id), [
            '_method' => 'PUT',
            'name' => $venue->name,
            'image' => $file,
        ]);

    $response->assertRedirect(route('admin.venues.index'));
    $freshVenue = $venue->fresh();
    expect($freshVenue->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($freshVenue->image_path);
});

test('venue scoped admin can upload assigned venue cover image via settings', function () {
    Storage::fake('public');

    $venue = Venue::factory()->create();
    $admin = User::factory()->create(['venue_id' => $venue->id]);
    $admin->assignRole(RoleName::Admin->value);

    $file = UploadedFile::fake()->create('venue_cover.png', 100, 'image/png');

    $response = $this->actingAs($admin)
        ->post(route('admin.settings.update'), [
            '_method' => 'PUT',
            'name' => $venue->name,
            'image' => $file,
        ]);

    $response->assertRedirect(route('admin.settings.edit'));
    $freshVenue = $venue->fresh();
    expect($freshVenue->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($freshVenue->image_path);
});

test('staff cannot upload venue cover image', function () {
    Storage::fake('public');

    $venue = Venue::factory()->create();
    $staff = User::factory()->create(['venue_id' => $venue->id]);
    $staff->assignRole(RoleName::Staff->value);

    $file = UploadedFile::fake()->create('venue_cover.png', 100, 'image/png');

    $this->actingAs($staff)
        ->post(route('admin.venues.update', $venue->id), [
            '_method' => 'PUT',
            'name' => $venue->name,
            'image' => $file,
        ])
        ->assertForbidden();
});

test('super admin can delete venue cover image via form update', function () {
    Storage::fake('public');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $path = UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg')->store('venues', 'public');
    $venue = Venue::factory()->create(['image_path' => $path]);

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.venues.update', $venue->id), [
            '_method' => 'PUT',
            'name' => $venue->name,
            'delete_image' => true,
        ]);

    $response->assertRedirect(route('admin.venues.index'));
    expect($venue->fresh()->image_path)->toBeNull();
});

test('super admin can delete venue cover image via destroy image route', function () {
    Storage::fake('public');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $path = UploadedFile::fake()->create('cover.jpg', 100, 'image/jpeg')->store('venues', 'public');
    $venue = Venue::factory()->create(['image_path' => $path]);

    $response = $this->actingAs($superAdmin)
        ->delete(route('admin.venues.image.destroy', $venue->id));

    $response->assertRedirect();
    expect($venue->fresh()->image_path)->toBeNull();
});
