<?php

use App\Enums\RoleName;
use App\Models\Court;
use App\Models\CourtImage;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('super admin can upload court image', function () {
    Storage::fake('public');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $court = Court::factory()->create();
    $file = UploadedFile::fake()->create('court_photo.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($superAdmin)
        ->post(route('admin.courts.images.store', $court->id), [
            'image' => $file,
            'is_primary' => true,
        ]);

    $response->assertRedirect();
    expect($court->images()->count())->toBe(1);
    expect($court->primaryImage)->not->toBeNull();
});

test('admin can upload court image for court in assigned venue', function () {
    Storage::fake('public');

    $venue = Venue::factory()->create();
    $admin = User::factory()->create(['venue_id' => $venue->id]);
    $admin->assignRole(RoleName::Admin->value);

    $court = Court::factory()->create(['venue_id' => $venue->id]);
    $file = UploadedFile::fake()->create('court.png', 100, 'image/png');

    $response = $this->actingAs($admin)
        ->post(route('admin.courts.images.store', $court->id), [
            'image' => $file,
        ]);

    $response->assertRedirect();
    expect($court->images()->count())->toBe(1);
});

test('admin cannot upload court image for court outside assigned venue', function () {
    Storage::fake('public');

    $venue1 = Venue::factory()->create();
    $venue2 = Venue::factory()->create();

    $admin = User::factory()->create(['venue_id' => $venue1->id]);
    $admin->assignRole(RoleName::Admin->value);

    $otherCourt = Court::factory()->create(['venue_id' => $venue2->id]);
    $file = UploadedFile::fake()->create('court.png', 100, 'image/png');

    $this->actingAs($admin)
        ->post(route('admin.courts.images.store', $otherCourt->id), [
            'image' => $file,
        ])
        ->assertForbidden();
});

test('staff can upload and set primary court image for assigned court', function () {
    Storage::fake('public');

    $staff = User::factory()->create();
    $staff->assignRole(RoleName::Staff->value);

    $court = Court::factory()->create();
    $staff->assignedCourts()->attach($court);

    $file = UploadedFile::fake()->create('staff_court.jpg', 100, 'image/jpeg');

    $response = $this->actingAs($staff)
        ->post(route('staff.courts.images.store', $court->id), [
            'image' => $file,
            'is_primary' => true,
        ]);

    $response->assertRedirect();
    expect($court->images()->count())->toBe(1);
    expect($court->primaryImage)->not->toBeNull();
});

test('staff cannot delete court image', function () {
    Storage::fake('public');

    $staff = User::factory()->create();
    $staff->assignRole(RoleName::Staff->value);

    $court = Court::factory()->create();
    $staff->assignedCourts()->attach($court);

    $image = CourtImage::factory()->create(['court_id' => $court->id]);

    $this->actingAs($staff)
        ->delete(route('staff.courts.images.destroy', [$court->id, $image->id]))
        ->assertForbidden();

    expect($court->images()->count())->toBe(1);
});

test('admin can delete court image for court in assigned venue', function () {
    Storage::fake('public');

    $venue = Venue::factory()->create();
    $admin = User::factory()->create(['venue_id' => $venue->id]);
    $admin->assignRole(RoleName::Admin->value);

    $court = Court::factory()->create(['venue_id' => $venue->id]);
    $imagePath = UploadedFile::fake()->create('court.jpg', 100, 'image/jpeg')->store('courts', 'public');
    $image = CourtImage::factory()->create(['court_id' => $court->id, 'path' => $imagePath]);

    $response = $this->actingAs($admin)
        ->delete(route('admin.courts.images.destroy', [$court->id, $image->id]));

    $response->assertRedirect();
    expect($court->images()->count())->toBe(0);
});
