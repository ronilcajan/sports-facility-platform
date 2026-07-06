<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\CourtImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourtImage>
 */
class CourtImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'court_id' => Court::factory(),
            'path' => 'courts/'.fake()->uuid().'.jpg',
            'is_primary' => false,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Mark this image as the court's primary image.
     */
    public function primary(): static
    {
        return $this->state(fn (): array => ['is_primary' => true, 'sort_order' => 0]);
    }
}
