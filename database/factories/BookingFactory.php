<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
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
            'user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('09#########'),
            'date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'time_slots' => [fake()->randomElement(['08:00 AM', '09:00 AM', '10:00 AM'])],
            'notes' => fake()->optional()->sentence(),
            'total_price' => fake()->randomFloat(2, 20, 200),
            'receipt_path' => null,
            'status' => 'confirmed',
        ];
    }
}
