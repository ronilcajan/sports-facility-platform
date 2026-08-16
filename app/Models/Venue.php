<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $gcash_number
 * @property string|null $gcash_qr_path
 * @property string|null $maya_number
 * @property string|null $maya_qr_path
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'name',
    'slug',
    'description',
    'address',
    'phone',
    'email',
    'image_path',
    'gcash_number',
    'gcash_qr_path',
    'maya_number',
    'maya_qr_path',
    'is_active',
])]
class Venue extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Get the full URL for the venue cover image.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return asset('storage/'.$this->image_path);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Full public URL for a stored QR image path (null when not set).
     */
    private function qrUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.$path);
    }

    /**
     * Payment methods configured for this venue, shaped for the public booking flow.
     * Only methods with a number set are included.
     *
     * @return array<string, array{number: string, qr_url: string|null}>
     */
    public function paymentMethods(): array
    {
        $methods = [];

        if ($this->gcash_number) {
            $methods['gcash'] = [
                'number' => $this->gcash_number,
                'qr_url' => $this->qrUrl($this->gcash_qr_path),
            ];
        }

        if ($this->maya_number) {
            $methods['maya'] = [
                'number' => $this->maya_number,
                'qr_url' => $this->qrUrl($this->maya_qr_path),
            ];
        }

        return $methods;
    }

    /**
     * Courts that belong to this venue.
     *
     * @return HasMany<Court, $this>
     */
    public function courts(): HasMany
    {
        return $this->hasMany(Court::class);
    }

    /**
     * Users (admins/staff) associated with this venue.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Gallery photos that belong to this venue.
     *
     * @return HasMany<VenueImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(VenueImage::class)->orderBy('sort_order');
    }

    /**
     * The venue's hero gallery photo.
     *
     * @return HasOne<VenueImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(VenueImage::class)->where('is_primary', true);
    }

    /**
     * Best available cover photo — the dedicated cover, else the gallery hero.
     */
    public function coverImageUrl(): ?string
    {
        return $this->image_url ?? $this->primaryImage?->url;
    }

    /**
     * Gallery photos shaped for the admin and public payloads.
     *
     * @return array<int, array{id: int, path: string, url: string, is_primary: bool, sort_order: int}>
     */
    public function galleryPayload(): array
    {
        return $this->images
            ->map(fn (VenueImage $image): array => [
                'id' => $image->id,
                'path' => $image->path,
                'url' => $image->url,
                'is_primary' => $image->is_primary,
                'sort_order' => $image->sort_order,
            ])
            ->all();
    }
}
