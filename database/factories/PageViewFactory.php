<?php

namespace Database\Factories;

use App\Enums\DeviceType;
use App\Enums\TrafficSource;
use App\Models\PageView;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PageView>
 */
class PageViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_id' => hash('sha256', Str::uuid()->toString()),
            'session_id' => hash('sha256', Str::uuid()->toString()),
            'path' => '/',
            'route_name' => 'home',
            'referrer_host' => null,
            'source' => TrafficSource::Direct,
            'device' => DeviceType::Desktop,
            'viewed_at' => now(),
        ];
    }

    /**
     * All views belong to one visitor and one session.
     */
    public function forSession(string $sessionId, ?string $visitorId = null): static
    {
        return $this->state(fn (): array => [
            'session_id' => $sessionId,
            'visitor_id' => $visitorId ?? $sessionId,
        ]);
    }
}
