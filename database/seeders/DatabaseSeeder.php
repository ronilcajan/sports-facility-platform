<?php

namespace Database\Seeders;

use App\Enums\CourtStatus;
use App\Enums\RoleName;
use App\Enums\SportType;
use App\Models\Court;
use App\Models\CourtImage;
use App\Models\User;
use App\Models\Venue;
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

        // Seed 3 Distinct Venues (Each having Court A, Court B, Court C)
        $venue1 = Venue::firstOrCreate(
            ['slug' => 'metro-sports-center'],
            [
                'name' => 'Metro Sports Center',
                'description' => 'Premier indoor and outdoor sports facility featuring 3 professional-grade courts.',
                'address' => '123 Sports Avenue, Metro City',
                'phone' => '(555) 123-4567',
                'email' => 'info@metrosportscenter.com',
                'is_active' => true,
            ]
        );

        $venue2 = Venue::firstOrCreate(
            ['slug' => 'bayside-pickleball-club'],
            [
                'name' => 'Bayside Pickleball Club',
                'description' => 'Oceanfront facility with 3 covered tournament courts and lounge area.',
                'address' => '789 Harbor Road, Bayside',
                'phone' => '(555) 987-6543',
                'email' => 'contact@baysidepickleball.com',
                'is_active' => true,
            ]
        );

        $venue3 = Venue::firstOrCreate(
            ['slug' => 'downtown-smash-arena'],
            [
                'name' => 'Downtown Smash Arena',
                'description' => 'State-of-the-art indoor climate-controlled arena with 3 pro match courts.',
                'address' => '456 Central Boulevard, Downtown',
                'phone' => '(555) 456-7890',
                'email' => 'arena@downtownsmash.com',
                'is_active' => true,
            ]
        );

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
        $admin->update(['venue_id' => $venue1->id]);

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

        // Venue 1 Courts: Court A, Court B, Court C
        $v1_courtA = Court::firstOrCreate(
            ['slug' => 'metro-court-a'],
            [
                'venue_id' => $venue1->id,
                'name' => 'Court A',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court A at Metro Sports Center - Outdoor Pro Court.',
                'status' => CourtStatus::Available,
                'base_price' => 25.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $v1_courtB = Court::firstOrCreate(
            ['slug' => 'metro-court-b'],
            [
                'venue_id' => $venue1->id,
                'name' => 'Court B',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court B at Metro Sports Center - Covered Canopy Court.',
                'status' => CourtStatus::Available,
                'base_price' => 30.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $v1_courtC = Court::firstOrCreate(
            ['slug' => 'metro-court-c'],
            [
                'venue_id' => $venue1->id,
                'name' => 'Court C',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court C at Metro Sports Center - Stadium Indoor Court.',
                'status' => CourtStatus::Available,
                'base_price' => 35.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        // Venue 2 Courts: Court A, Court B, Court C
        $v2_courtA = Court::firstOrCreate(
            ['slug' => 'bayside-court-a'],
            [
                'venue_id' => $venue2->id,
                'name' => 'Court A',
                'sport_type' => SportType::Tennis,
                'description' => 'Court A at Bayside Club - Full-size oceanfront hard court.',
                'status' => CourtStatus::Available,
                'base_price' => 40.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $v2_courtB = Court::firstOrCreate(
            ['slug' => 'bayside-court-b'],
            [
                'venue_id' => $venue2->id,
                'name' => 'Court B',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court B at Bayside Club - Floodlit evening match court.',
                'status' => CourtStatus::Available,
                'base_price' => 28.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $v2_courtC = Court::firstOrCreate(
            ['slug' => 'bayside-court-c'],
            [
                'venue_id' => $venue2->id,
                'name' => 'Court C',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court C at Bayside Club - Coastal canopy court.',
                'status' => CourtStatus::Available,
                'base_price' => 32.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        // Venue 3 Courts: Court A, Court B, Court C
        $v3_courtA = Court::firstOrCreate(
            ['slug' => 'arena-court-a'],
            [
                'venue_id' => $venue3->id,
                'name' => 'Court A',
                'sport_type' => SportType::Badminton,
                'description' => 'Court A at Downtown Smash Arena - Sprung wooden floor badminton court.',
                'status' => CourtStatus::Available,
                'base_price' => 22.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $v3_courtB = Court::firstOrCreate(
            ['slug' => 'arena-court-b'],
            [
                'venue_id' => $venue3->id,
                'name' => 'Court B',
                'sport_type' => SportType::Basketball,
                'description' => 'Court B at Downtown Smash Arena - Full-size hardwood basketball court.',
                'status' => CourtStatus::Available,
                'base_price' => 50.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $v3_courtC = Court::firstOrCreate(
            ['slug' => 'arena-court-c'],
            [
                'venue_id' => $venue3->id,
                'name' => 'Court C',
                'sport_type' => SportType::Futsal,
                'description' => 'Court C at Downtown Smash Arena - Professional synthetic futsal court.',
                'status' => CourtStatus::Available,
                'base_price' => 45.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $courts = collect([
            $v1_courtA, $v1_courtB, $v1_courtC,
            $v2_courtA, $v2_courtB, $v2_courtC,
            $v3_courtA, $v3_courtB, $v3_courtC,
        ]);

        // Seed separate court staff accounts
        $staff1 = User::firstOrCreate(
            ['email' => 'staff1@example.com'],
            ['name' => 'Court Staff 1', 'password' => bcrypt('password')]
        );
        $staff1->assignRole(RoleName::Staff->value);
        $staff1->assignedCourts()->sync([$v1_courtA->id, $v1_courtB->id, $v1_courtC->id]);

        $staff2 = User::firstOrCreate(
            ['email' => 'staff2@example.com'],
            ['name' => 'Court Staff 2', 'password' => bcrypt('password')]
        );
        $staff2->assignRole(RoleName::Staff->value);
        $staff2->assignedCourts()->sync([$v2_courtA->id, $v2_courtB->id, $v2_courtC->id]);

        $staff3 = User::firstOrCreate(
            ['email' => 'staff3@example.com'],
            ['name' => 'Court Staff 3', 'password' => bcrypt('password')]
        );
        $staff3->assignRole(RoleName::Staff->value);
        $staff3->assignedCourts()->sync([$v3_courtA->id, $v3_courtB->id, $v3_courtC->id]);

        $staff->assignedCourts()->sync([$v1_courtA->id, $v1_courtB->id, $v1_courtC->id]);

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
