<?php

use App\Enums\CourtStatus;
use App\Enums\RoleName;
use App\Models\Court;
use App\Models\CourtImage;
use App\Models\User;
use App\Models\Venue;
use App\Models\VenueImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

test('a super admin can upload several photos to one venue', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $venue = Venue::factory()->create();

    foreach (['one.jpg', 'two.jpg', 'three.jpg'] as $filename) {
        $this->actingAs($superAdmin)
            ->post(route('admin.venues.images.store', $venue), [
                'image' => UploadedFile::fake()->image($filename, 1200, 800),
            ])
            ->assertRedirect();
    }

    expect(VenueImage::where('venue_id', $venue->id)->count())->toBe(3);

    // The first upload becomes the hero; the rest queue behind it.
    expect(VenueImage::where('venue_id', $venue->id)->where('is_primary', true)->count())->toBe(1);

    $this->actingAs($superAdmin)
        ->get(route('admin.venues.show', $venue))
        ->assertInertia(fn ($page) => $page->has('venue.images', 3));
});

test('uploaded venue photos are stored on the public disk', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $venue = Venue::factory()->create();

    $this->actingAs($superAdmin)
        ->post(route('admin.venues.images.store', $venue), [
            'image' => UploadedFile::fake()->image('cover.jpg', 1200, 800),
        ])->assertRedirect();

    $image = VenueImage::where('venue_id', $venue->id)->firstOrFail();

    Storage::disk('public')->assertExists($image->path);
    expect($image->path)->toStartWith('venues/');
});

test('the primary venue photo can be changed and photos deleted', function (): void {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $venue = Venue::factory()->create();
    $first = VenueImage::factory()->create(['venue_id' => $venue->id, 'is_primary' => true]);
    $second = VenueImage::factory()->create(['venue_id' => $venue->id, 'is_primary' => false]);

    $this->actingAs($superAdmin)
        ->patch(route('admin.venues.images.primary', [$venue, $second]))
        ->assertRedirect();

    expect($second->fresh()->is_primary)->toBeTrue()
        ->and($first->fresh()->is_primary)->toBeFalse();

    $this->actingAs($superAdmin)
        ->delete(route('admin.venues.images.destroy', [$venue, $second]))
        ->assertRedirect();

    $this->assertDatabaseMissing('venue_images', ['id' => $second->id]);

    // Deleting the hero promotes whatever remains.
    expect($first->fresh()->is_primary)->toBeTrue();
});

test('a venue admin cannot touch another venue gallery', function (): void {
    $ownVenue = Venue::factory()->create();
    $otherVenue = Venue::factory()->create();

    $admin = User::factory()->create(['venue_id' => $ownVenue->id]);
    $admin->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)
        ->post(route('admin.venues.images.store', $otherVenue), [
            'image' => UploadedFile::fake()->image('sneaky.jpg'),
        ])
        ->assertForbidden();

    expect(VenueImage::where('venue_id', $otherVenue->id)->count())->toBe(0);
});

test('the public venue page shows venue photos instead of court photos when present', function (): void {
    $venue = Venue::factory()->create();
    VenueImage::factory()->create(['venue_id' => $venue->id, 'is_primary' => true, 'path' => 'venues/hero.webp']);
    VenueImage::factory()->create(['venue_id' => $venue->id, 'path' => 'venues/second.webp']);

    $this->get(route('site.venues.show', $venue))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('venue.images', 2));
});

test('the public venue page falls back to court photos when the venue has none', function (): void {
    $venue = Venue::factory()->create();
    $court = Court::factory()->create([
        'venue_id' => $venue->id,
        'status' => CourtStatus::Available,
        'is_active' => true,
    ]);
    CourtImage::factory()->create(['court_id' => $court->id, 'path' => 'courts/one.webp']);

    $this->get(route('site.venues.show', $venue))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('venue.images', 1));
});
