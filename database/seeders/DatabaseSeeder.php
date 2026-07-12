<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Court;
use App\Models\CourtImage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('superadmin@example.com'),
        ]);
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'email' => 'admin@example.com',

        ]);
        $admin->assignRole(RoleName::Admin->value);

        $staff = User::factory()->create([
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'email' => 'staff@example.com',

        ]);
        $staff->assignRole(RoleName::Staff->value);

        $customer = User::factory()->create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'email' => 'customer@example.com',

        ]);
        $customer->assignRole(RoleName::Customer->value);

        $courts = Court::factory()->count(6)->create();

        // Seed court images
        foreach ($courts as $court) {
            CourtImage::factory()->primary()->create([
                'court_id' => $court->id,
                'path' => 'courts/court_pickleball.png',
            ]);

            CourtImage::factory()->create([
                'court_id' => $court->id,
                'path' => 'courts/hero_pickleball.png',
                'is_primary' => false,
                'sort_order' => 1,
            ]);

            CourtImage::factory()->create([
                'court_id' => $court->id,
                'path' => 'courts/cta_pickleball.png',
                'is_primary' => false,
                'sort_order' => 2,
            ]);

            CourtImage::factory()->create([
                'court_id' => $court->id,
                'path' => 'courts/court_pickleball.png',
                'is_primary' => false,
                'sort_order' => 3,
            ]);
        }

        // Assign the staff member to the first two courts for local testing.
        $staff->assignedCourts()->sync($courts->take(2)->pluck('id'));
    }
}
