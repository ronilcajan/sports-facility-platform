<?php

namespace Database\Factories;

use App\Enums\CourtStatus;
use App\Enums\SportType;
use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->streetName().' Court';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'sport_type' => SportType::Pickleball,
            'description' => fake()->optional()->paragraph(),
            'status' => CourtStatus::Available,
            'base_price' => fake()->randomFloat(2, 10, 80),
            'slot_duration_minutes' => fake()->randomElement([30, 60, 90]),
            'buffer_minutes' => fake()->randomElement([0, 5, 10, 15]),
            'is_active' => true,
        ];
    }

    /**
     * Court is available for booking.
     */
    public function available(): static
    {
        return $this->state(fn (): array => ['status' => CourtStatus::Available]);
    }

    /**
     * Court is under maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (): array => ['status' => CourtStatus::Maintenance]);
    }

    /**
     * Court is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (): array => ['status' => CourtStatus::Closed]);
    }
}
