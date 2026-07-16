<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Sports Complex';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'description' => fake()->optional()->paragraph(),
            'address' => fake()->address(),
            'phone' => fake()->numerify('09#########'),
            'email' => fake()->safeEmail(),
            'gcash_number' => null,
            'maya_number' => null,
            'is_active' => true,
        ];
    }
}
