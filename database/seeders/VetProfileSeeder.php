<?php

namespace Database\Seeders;

use App\Models\VetProfile;
use App\Models\VetAvailability;
use Illuminate\Database\Seeder;

class VetProfileSeeder extends Seeder
{
    public function run(): void
    {
        $vets = [
            [
                'clinic_name' => 'Downtown Pet Emergency',
                'vet_name' => 'Dr. Sarah Johnson',
                'phone' => '+1-555-0101',
                'email' => 'info@downtownpetemergency.com',
                'address' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'services' => ['emergency', 'surgery', 'x-ray', 'dental'],
                'accepted_species' => ['dog', 'cat', 'rabbit'],
                'is_emergency_available' => true,
                'is_24_hours' => true,
                'is_verified' => true,
                'is_active' => true,
                'rating' => 4.8,
                'review_count' => 156,
            ],
            [
                'clinic_name' => 'Riverside Animal Hospital',
                'vet_name' => 'Dr. Michael Chen',
                'phone' => '+1-555-0102',
                'email' => 'care@riversideanimalhospital.com',
                'address' => '456 River Road',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10002',
                'latitude' => 40.7200,
                'longitude' => -73.9950,
                'services' => ['general', 'vaccination', 'dental', 'grooming'],
                'accepted_species' => ['dog', 'cat', 'bird', 'hamster'],
                'is_emergency_available' => false,
                'is_24_hours' => false,
                'is_verified' => true,
                'is_active' => true,
                'rating' => 4.5,
                'review_count' => 89,
            ],
            [
                'clinic_name' => 'Pet Care Plus',
                'vet_name' => 'Dr. Emily Rodriguez',
                'phone' => '+1-555-0103',
                'email' => 'hello@petcareplus.com',
                'address' => '789 Oak Avenue',
                'city' => 'Brooklyn',
                'state' => 'NY',
                'postal_code' => '11201',
                'latitude' => 40.6892,
                'longitude' => -73.9857,
                'services' => ['emergency', 'surgery', 'x-ray', 'ultrasound', 'lab'],
                'accepted_species' => ['dog', 'cat', 'reptile', 'bird'],
                'is_emergency_available' => true,
                'is_24_hours' => false,
                'is_verified' => true,
                'is_active' => true,
                'rating' => 4.9,
                'review_count' => 234,
            ],
            [
                'clinic_name' => 'Queens Veterinary Center',
                'vet_name' => 'Dr. James Wilson',
                'phone' => '+1-555-0104',
                'email' => 'contact@queensvet.com',
                'address' => '321 Queens Blvd',
                'city' => 'Queens',
                'state' => 'NY',
                'postal_code' => '11375',
                'latitude' => 40.7282,
                'longitude' => -73.8317,
                'services' => ['general', 'vaccination', 'dental', 'surgery'],
                'accepted_species' => ['dog', 'cat'],
                'is_emergency_available' => true,
                'is_24_hours' => true,
                'is_verified' => true,
                'is_active' => true,
                'rating' => 4.6,
                'review_count' => 178,
            ],
            [
                'clinic_name' => 'Bronx Animal Care',
                'vet_name' => 'Dr. Lisa Park',
                'phone' => '+1-555-0105',
                'email' => 'info@bronxanimalcare.com',
                'address' => '555 Grand Concourse',
                'city' => 'Bronx',
                'state' => 'NY',
                'postal_code' => '10451',
                'latitude' => 40.8176,
                'longitude' => -73.9219,
                'services' => ['general', 'emergency', 'x-ray', 'lab'],
                'accepted_species' => ['dog', 'cat', 'rabbit', 'fish'],
                'is_emergency_available' => true,
                'is_24_hours' => false,
                'is_verified' => false,
                'is_active' => true,
                'rating' => 4.3,
                'review_count' => 67,
            ],
        ];

        foreach ($vets as $vetData) {
            $vet = VetProfile::updateOrCreate(
                ['email' => $vetData['email']],
                $vetData
            );

            // Add availability schedule
            $this->createAvailability($vet);
        }
    }

    private function createAvailability(VetProfile $vet): void
    {
        // Clear existing
        $vet->availabilities()->delete();

        if ($vet->is_24_hours) {
            // 24/7 availability
            for ($day = 0; $day <= 6; $day++) {
                VetAvailability::create([
                    'vet_profile_id' => $vet->id,
                    'day_of_week' => $day,
                    'open_time' => '00:00',
                    'close_time' => '23:59',
                    'is_emergency_hours' => true,
                ]);
            }
        } else {
            // Regular hours: Mon-Fri 8am-6pm, Sat 9am-3pm
            for ($day = 1; $day <= 5; $day++) {
                VetAvailability::create([
                    'vet_profile_id' => $vet->id,
                    'day_of_week' => $day,
                    'open_time' => '08:00',
                    'close_time' => '18:00',
                    'is_emergency_hours' => false,
                ]);
            }
            // Saturday
            VetAvailability::create([
                'vet_profile_id' => $vet->id,
                'day_of_week' => 6,
                'open_time' => '09:00',
                'close_time' => '15:00',
                'is_emergency_hours' => false,
            ]);

            // Emergency hours if available
            if ($vet->is_emergency_available) {
                for ($day = 0; $day <= 6; $day++) {
                    VetAvailability::create([
                        'vet_profile_id' => $vet->id,
                        'day_of_week' => $day,
                        'open_time' => '18:00',
                        'close_time' => '23:00',
                        'is_emergency_hours' => true,
                    ]);
                }
            }
        }
    }
}
