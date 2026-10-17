<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = array_keys(Expense::CATEGORIES);
        $paymentMethods = array_keys(Expense::PAYMENT_METHODS);

        return [
            'title' => fake()->randomElement([
                'Court 1 Light Replacement',
                'Monthly Electricity Bill',
                'Water Utility Bill',
                'Shuttlecocks Bulk Restock',
                'Net Replacement & Post Anchoring',
                'Janitorial & Disinfectant Supplies',
                'Weekly Staff Payroll',
                'Air Conditioning Maintenance',
                'Sound System Cable Repairs',
                'Social Media Ad Campaign',
            ]),
            'amount' => fake()->randomFloat(2, 250, 15000),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'category' => fake()->randomElement($categories),
            'venue_id' => Venue::factory(),
            'user_id' => User::factory(),
            'payment_method' => fake()->randomElement($paymentMethods),
            'reference_number' => 'EXP-'.fake()->unique()->numerify('#####'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
