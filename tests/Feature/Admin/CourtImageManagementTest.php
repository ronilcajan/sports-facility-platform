<?php

use App\Enums\RoleName;
use App\Models\Court;
use App\Models\CourtImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('super admin can upload primary hero display image for a court', function () {
    Storage::fake('public');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $court = Court::factory()->create();

    $file = UploadedFile::fake()->create('court_hero.jpg', 100, 'image/jpeg');

    $this->actingAs($superAdmin)
        ->post(route('admin.courts.images.store', $court->id), [
            'image' => $file,
            'is_primary' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('court_images', [
        'court_id' => $court->id,
        'is_primary' => true,
    ]);

    $image = CourtImage::where('court_id', $court->id)->first();
    Storage::disk('public')->assertExists($image->path);
});

test('super admin can change primary hero image and delete an image', function () {
    Storage::fake('public');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(RoleName::SuperAdmin->value);

    $court = Court::factory()->create();

    $image1 = CourtImage::factory()->create(['court_id' => $court->id, 'is_primary' => true, 'path' => 'courts/img1.jpg']);
    $image2 = CourtImage::factory()->create(['court_id' => $court->id, 'is_primary' => false, 'path' => 'courts/img2.jpg']);

    $this->actingAs($superAdmin)
        ->patch(route('admin.courts.images.primary', [$court->id, $image2->id]))
        ->assertRedirect();

    expect($image2->fresh()->is_primary)->toBeTrue();
    expect($image1->fresh()->is_primary)->toBeFalse();

    $this->actingAs($superAdmin)
        ->delete(route('admin.courts.images.destroy', [$court->id, $image1->id]))
        ->assertRedirect();

    $this->assertDatabaseMissing('court_images', ['id' => $image1->id]);
});
