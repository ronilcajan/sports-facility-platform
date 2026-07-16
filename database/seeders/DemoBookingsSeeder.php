<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\Court;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Scatters a large number of bookings across the days around today (and the
 * following weeks) so the bookings calendar board and dashboards have plenty of
 * data to view. Additive — safe to run on top of the base DatabaseSeeder.
 */
class DemoBookingsSeeder extends Seeder
{
    public function run(): void
    {
        $courts = Court::all();

        if ($courts->isEmpty()) {
            $this->call(DatabaseSeeder::class);
            $courts = Court::all();
        }

        // Ensure a healthy pool of customers to attribute bookings to.
        $customers = User::role(RoleName::Customer->value)->get();

        while ($customers->count() < 15) {
            $customer = User::factory()->create();
            $customer->assignRole(RoleName::Customer->value);
            $customers->push($customer);
        }

        $slotPool = [
            '06:00 AM', '07:00 AM', '08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM',
            '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM',
            '06:00 PM', '07:00 PM', '08:00 PM',
        ];

        $today = CarbonImmutable::now()->startOfDay();
        $start = $today->subDays(3);
        $created = 0;

        // Cover the previous few days through the next few weeks.
        for ($dayOffset = 0; $dayOffset < 24; $dayOffset++) {
            $date = $start->addDays($dayOffset);
            $isPast = $date->lessThan($today);

            foreach ($courts as $court) {
                $bookingsToday = random_int(0, 4);
                $usedSlots = [];

                for ($n = 0; $n < $bookingsToday; $n++) {
                    $slotIndex = random_int(0, count($slotPool) - 2);

                    if (in_array($slotIndex, $usedSlots, true)) {
                        continue;
                    }
                    $usedSlots[] = $slotIndex;

                    $slots = [$slotPool[$slotIndex]];
                    if (random_int(0, 1) === 1) {
                        $slots[] = $slotPool[$slotIndex + 1];
                    }

                    $status = $isPast
                        ? fake()->randomElement(['completed', 'completed', 'confirmed', 'cancelled'])
                        : fake()->randomElement(['pending', 'pending', 'approved', 'confirmed', 'confirmed', 'cancelled', 'rejected']);

                    Booking::factory()->create([
                        'court_id' => $court->id,
                        'user_id' => fake()->boolean(70) ? $customers->random()->id : null,
                        'date' => $date->toDateString(),
                        'time_slots' => $slots,
                        'total_price' => round((float) $court->base_price * count($slots), 2),
                        'status' => $status,
                    ]);

                    $created++;
                }
            }
        }

        $this->command?->info("Seeded {$created} demo bookings across {$courts->count()} courts and 24 days.");
    }
}
