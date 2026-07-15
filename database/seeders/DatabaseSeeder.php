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

        // Seed 3 Distinct Venues (One-to-Many Venue->Courts)
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
                'description' => 'Oceanfront facility with 2 covered tournament courts and lounge area.',
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

        // Venue 1 Courts (3 Courts: Court 1, Court 2, Court 3)
        $court1 = Court::firstOrCreate(
            ['slug' => 'court-1'],
            [
                'venue_id' => $venue1->id,
                'name' => 'Court 1 - Outdoor Pro',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court 1 at Metro Sports Center - Premium outdoor pickleball court.',
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
                'venue_id' => $venue1->id,
                'name' => 'Court 2 - Covered Canopy',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court 2 at Metro Sports Center - Covered pro-grade pickleball court.',
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
                'venue_id' => $venue1->id,
                'name' => 'Court 3 - Stadium Indoor',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court 3 at Metro Sports Center - Stadium lighting indoor court.',
                'status' => CourtStatus::Available,
                'base_price' => 35.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        // Venue 2 Courts (2 Courts: Court 4, Court 5)
        $court4 = Court::firstOrCreate(
            ['slug' => 'court-4'],
            [
                'venue_id' => $venue2->id,
                'name' => 'Court 4 - Oceanview Deck',
                'sport_type' => SportType::Tennis,
                'description' => 'Court 4 at Bayside Club - Full-size oceanfront hard court.',
                'status' => CourtStatus::Available,
                'base_price' => 40.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $court5 = Court::firstOrCreate(
            ['slug' => 'court-5'],
            [
                'venue_id' => $venue2->id,
                'name' => 'Court 5 - Sunset Pickleball',
                'sport_type' => SportType::Pickleball,
                'description' => 'Court 5 at Bayside Club - Floodlit evening match court.',
                'status' => CourtStatus::Available,
                'base_price' => 28.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        // Venue 3 Courts (3 Courts: Court 6, Court 7, Court 8)
        $court6 = Court::firstOrCreate(
            ['slug' => 'court-6'],
            [
                'venue_id' => $venue3->id,
                'name' => 'Court 6 - Arena Alpha',
                'sport_type' => SportType::Badminton,
                'description' => 'Court 6 at Downtown Smash Arena - Sprung wooden floor badminton court.',
                'status' => CourtStatus::Available,
                'base_price' => 22.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $court7 = Court::firstOrCreate(
            ['slug' => 'court-7'],
            [
                'venue_id' => $venue3->id,
                'name' => 'Court 7 - Arena Beta',
                'sport_type' => SportType::Basketball,
                'description' => 'Court 7 at Downtown Smash Arena - Full-size hardwood basketball court.',
                'status' => CourtStatus::Available,
                'base_price' => 50.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $court8 = Court::firstOrCreate(
            ['slug' => 'court-8'],
            [
                'venue_id' => $venue3->id,
                'name' => 'Court 8 - Arena Gamma',
                'sport_type' => SportType::Futsal,
                'description' => 'Court 8 at Downtown Smash Arena - Professional synthetic futsal court.',
                'status' => CourtStatus::Available,
                'base_price' => 45.00,
                'slot_duration_minutes' => 60,
                'buffer_minutes' => 0,
                'is_active' => true,
            ]
        );

        $courts = collect([$court1, $court2, $court3, $court4, $court5, $court6, $court7, $court8]);

        // Seed 3 separate court staff accounts
        $staff1 = User::firstOrCreate(
            ['email' => 'staff1@example.com'],
            ['name' => 'Court Staff 1', 'password' => bcrypt('password')]
        );
        $staff1->assignRole(RoleName::Staff->value);
        $staff1->assignedCourts()->sync([$court1->id, $court2->id, $court3->id]);

        $staff2 = User::firstOrCreate(
            ['email' => 'staff2@example.com'],
            ['name' => 'Court Staff 2', 'password' => bcrypt('password')]
        );
        $staff2->assignRole(RoleName::Staff->value);
        $staff2->assignedCourts()->sync([$court4->id, $court5->id]);

        $staff3 = User::firstOrCreate(
            ['email' => 'staff3@example.com'],
            ['name' => 'Court Staff 3', 'password' => bcrypt('password')]
        );
        $staff3->assignRole(RoleName::Staff->value);
        $staff3->assignedCourts()->sync([$court6->id, $court7->id, $court8->id]);

        $staff->assignedCourts()->sync([$court1->id, $court2->id, $court3->id]);

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
