<?php

namespace Database\Seeders;

use App\Enums\CourtStatus;
use App\Enums\RoleName;
use App\Enums\SportType;
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

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            ['name' => 'Super Admin', 'password' => bcrypt('superadmin@example.com')]
        );
        $superAdmin->assignRole(RoleName::SuperAdmin->value);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => bcrypt('admin@example.com')]
        );
        $admin->assignRole(RoleName::Admin->value);

        $staff = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            ['name' => 'Staff Member', 'password' => bcrypt('staff@example.com')]
        );
        $staff->assignRole(RoleName::Staff->value);

        $customer = User::firstOrCreate(
            ['email' => 'customer@example.com'],
            ['name' => 'Customer', 'password' => bcrypt('customer@example.com')]
        );
        $customer->assignRole(RoleName::Customer->value);

        // Ensure exactly 3 courts exist: Court 1, Court 2, Court 3
        $court1 = Court::firstOrCreate(
            ['slug' => 'court-1'],
            [
                'name' => 'Court 1',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court 1 - Premium outdoor pickleball court.',
                'status' => CourtStatus::Available,
                'base_price' => 25.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $court2 = Court::firstOrCreate(
            ['slug' => 'court-2'],
            [
                'name' => 'Court 2',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court 2 - Covered pro-grade pickleball court.',
                'status' => CourtStatus::Available,
                'base_price' => 30.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $court3 = Court::firstOrCreate(
            ['slug' => 'court-3'],
            [
                'name' => 'Court 3',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court 3 - Stadium lighting indoor court.',
                'status' => CourtStatus::Available,
                'base_price' => 35.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $courts = collect([$court1, $court2, $court3]);

        // Delete any courts other than Court 1, Court 2, Court 3 if they exist
        Court::whereNotIn('id', $courts->pluck('id'))->delete();

        // Seed 3 separate court staff accounts, each assigned to a single court
        $staff1 = User::firstOrCreate(
            ['email' => 'staff1@example.com'],
            ['name' => 'Court Staff 1', 'password' => bcrypt('password')]
        );
        $staff1->assignRole(RoleName::Staff->value);
        $staff1->assignedCourts()->sync([$court1->id]);

        $staff2 = User::firstOrCreate(
            ['email' => 'staff2@example.com'],
            ['name' => 'Court Staff 2', 'password' => bcrypt('password')]
        );
        $staff2->assignRole(RoleName::Staff->value);
        $staff2->assignedCourts()->sync([$court2->id]);

        $staff3 = User::firstOrCreate(
            ['email' => 'staff3@example.com'],
            ['name' => 'Court Staff 3', 'password' => bcrypt('password')]
        );
        $staff3->assignRole(RoleName::Staff->value);
        $staff3->assignedCourts()->sync([$court3->id]);

        $staff->assignedCourts()->sync([$court1->id]);

        // Seed court images for each court
        foreach ($courts as $court) {
            if ($court->images()->count() === 0) {
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
        }
    }
}
