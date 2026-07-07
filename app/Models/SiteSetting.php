<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 */
#[Fillable(['key', 'value'])]
class SiteSetting extends Model
{
    /**
     * Read a setting by key, falling back to the given default. Cached forever
     * and busted on write so hot paths (every request) avoid a DB round-trip.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // Cache an empty-string sentinel for the absent case: rememberForever
        // treats a cached null as a miss and would re-query every request, so
        // storing '' keeps the "not set" state genuinely cached.
        $value = Cache::rememberForever(
            "site_setting:{$key}",
            fn (): string => (string) (static::query()->where('key', $key)->value('value') ?? ''),
        );

        return $value === '' ? $default : $value;
    }

    /**
     * Write a setting by key and bust its cache entry.
     */
    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget("site_setting:{$key}");
    }
}
