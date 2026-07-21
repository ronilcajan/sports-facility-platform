<?php

use App\Enums\CourtStatus;
use App\Enums\RoleName;
use App\Enums\SportType;
use App\Models\Court;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * A valid court payload for store/update requests.
 *
 * @return array<string, mixed>
 */
function courtPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Center Court',
        'sport_type' => SportType::Pickleball->value,
        'description' => 'Premium indoor court.',
        'status' => CourtStatus::Available->value,
        'base_price' => 25.00,
        'slot_duration_minutes' => 60,
        'buffer_minutes' => 10,
        'is_active' => true,
    ], $overrides);
}

test('guests are redirected from the courts index', function () {
    $this->get(route('admin.courts.index'))->assertRedirect(route('login'));
});

test('customers are forbidden from the admin courts area', function () {
    $this->actingAs(userWithRole(RoleName::Customer));

    $this->get(route('admin.courts.index'))->assertForbidden();
});

test('staff are forbidden from the admin courts area', function () {
    $this->actingAs(userWithRole(RoleName::Staff));

    $this->get(route('admin.courts.index'))->assertForbidden();
});

test('admins can view the courts index', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    Court::factory()->count(2)->create();

    $this->get(route('admin.courts.index'))->assertOk();
});

test('admins can create a court', function () {
    $this->actingAs(userWithRole(RoleName::Admin));

    $response = $this->post(route('admin.courts.store'), courtPayload());

    $response->assertRedirect(route('admin.courts.index'));
    $this->assertDatabaseHas('courts', [
        'name' => 'Center Court',
        'slug' => 'center-court',
        'status' => CourtStatus::Available->value,
    ]);
});

test('court creation validates required fields', function () {
    $this->actingAs(userWithRole(RoleName::Admin));

    $this->post(route('admin.courts.store'), courtPayload(['name' => '']))
        ->assertSessionHasErrors('name');
});

test('duplicate names produce unique slugs', function () {
    $this->actingAs(userWithRole(RoleName::Admin));

    $this->post(route('admin.courts.store'), courtPayload(['name' => 'Grand Court']));
    $this->post(route('admin.courts.store'), courtPayload(['name' => 'Grand Court']));

    expect(Court::pluck('slug')->all())->toContain('grand-court', 'grand-court-2');
});

test('customers cannot create courts', function () {
    $this->actingAs(userWithRole(RoleName::Customer));

    $this->post(route('admin.courts.store'), courtPayload())->assertForbidden();
});

test('admins can update a court', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $court = Court::factory()->create(['name' => 'Old Name']);

    $this->put(route('admin.courts.update', $court), courtPayload(['name' => 'New Name']))
        ->assertRedirect(route('admin.courts.index'));

    expect($court->fresh()->name)->toBe('New Name')
        ->and($court->fresh()->slug)->toBe('new-name');
});

test('admins can soft delete a court', function () {
    $this->actingAs(userWithRole(RoleName::Admin));
    $court = Court::factory()->create();

    $this->delete(route('admin.courts.destroy', $court))
        ->assertRedirect(route('admin.courts.index'));

    $this->assertSoftDeleted($court);
});

test('super admins can manage courts', function () {
    $this->actingAs(userWithRole(RoleName::SuperAdmin));

    $this->post(route('admin.courts.store'), courtPayload())
        ->assertRedirect(route('admin.courts.index'));
});

test('admins can create a court with an uploaded primary image', function () {
    Storage::fake('public');
    $this->actingAs(userWithRole(RoleName::Admin));

    $file = UploadedFile::fake()->create('court.png', 10, 'image/png');
    $response = $this->post(route('admin.courts.store'), courtPayload([
        'image' => $file,
    ]));

    $response->assertRedirect(route('admin.courts.index'));
    $court = Court::where('name', 'Center Court')->firstOrFail();
    expect($court->primaryImage)->not->toBeNull();
    Storage::disk('public')->assertExists($court->primaryImage->path);
});

test('admins can update a court image', function () {
    Storage::fake('public');
    $this->actingAs(userWithRole(RoleName::Admin));
    $court = Court::factory()->create();

    $file = UploadedFile::fake()->create('new-court.jpg', 10, 'image/jpeg');
    $this->put(route('admin.courts.update', $court), courtPayload([
        'image' => $file,
    ]))->assertRedirect(route('admin.courts.index'));

    expect($court->fresh()->primaryImage)->not->toBeNull();
    Storage::disk('public')->assertExists($court->fresh()->primaryImage->path);
});
