<?php

namespace App\Models;

use App\Enums\DeviceType;
use App\Enums\TrafficSource;
use Carbon\CarbonInterface;
use Database\Factories\PageViewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A single recorded visit to a public page.
 *
 * @property int $id
 * @property string $visitor_id
 * @property string $session_id
 * @property string $path
 * @property string|null $route_name
 * @property string|null $referrer_host
 * @property TrafficSource $source
 * @property DeviceType $device
 * @property Carbon $viewed_at
 */
#[Fillable(['visitor_id', 'session_id', 'path', 'route_name', 'referrer_host', 'source', 'device', 'viewed_at'])]
class PageView extends Model
{
    /** @use HasFactory<PageViewFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => TrafficSource::class,
            'device' => DeviceType::class,
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * Limit to views recorded within the given window (inclusive of both ends).
     *
     * @param  Builder<PageView>  $query
     * @return Builder<PageView>
     */
    public function scopeBetweenDates(Builder $query, CarbonInterface $from, CarbonInterface $to): Builder
    {
        return $query->whereBetween('viewed_at', [$from, $to]);
    }
}
