<?php

namespace Database\Seeders;

use App\Models\VetProfile;
use App\Models\VetAvailability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
                'address' => '123 Main Street, New York City',
                'city' => 'New York City',
                'state' => 'NY',
                'postal_code' => '10001',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'services' => ['emergency', 'surgery', 'x-ray', 'dental'],
                'accepted_species' => ['dog', 'cat', 'rabbit'],
                'specialization' => 'Small Animal Emergency',
                'consultation_fee' => 1500,
                'home_visit_fee' => 2200,
                'is_emergency_available' => true,
                'is_24_hours' => true,
                'vet_status' => 'approved',
                'verification_status' => 'approved',
                'is_active' => true,
            ],
            [
                'clinic_name' => 'Riverside Animal Hospital',
                'vet_name' => 'Dr. Michael Chen',
                'phone' => '+1-555-0102',
                'email' => 'care@riversideanimalhospital.com',
                'address' => '456 River Road, New York City',
                'city' => 'New York City',
                'state' => 'NY',
                'postal_code' => '10002',
                'latitude' => 40.7200,
                'longitude' => -73.9950,
                'services' => ['general', 'vaccination', 'dental', 'grooming'],
                'accepted_species' => ['dog', 'cat', 'bird', 'hamster'],
                'specialization' => 'Companion Animal Practice',
                'consultation_fee' => 1200,
                'home_visit_fee' => 1800,
                'is_emergency_available' => false,
                'is_24_hours' => false,
                'vet_status' => 'approved',
                'verification_status' => 'approved',
                'is_active' => true,
            ],
            [
                'clinic_name' => 'Pet Care Plus',
                'vet_name' => 'Dr. Emily Rodriguez',
                'phone' => '+1-555-0103',
                'email' => 'hello@petcareplus.com',
                'address' => '789 Oak Avenue, Brooklyn',
                'city' => 'Brooklyn',
                'state' => 'NY',
                'postal_code' => '11201',
                'latitude' => 40.6892,
                'longitude' => -73.9857,
                'services' => ['emergency', 'surgery', 'x-ray', 'ultrasound', 'lab'],
                'accepted_species' => ['dog', 'cat', 'reptile', 'bird'],
                'specialization' => 'Emergency and Surgery',
                'consultation_fee' => 1400,
                'home_visit_fee' => 2100,
                'is_emergency_available' => true,
                'is_24_hours' => false,
                'vet_status' => 'approved',
                'verification_status' => 'approved',
                'is_active' => true,
            ],
            [
                'clinic_name' => 'Queens Veterinary Center',
                'vet_name' => 'Dr. James Wilson',
                'phone' => '+1-555-0104',
                'email' => 'contact@queensvet.com',
                'address' => '321 Queens Blvd, Queens',
                'city' => 'Queens',
                'state' => 'NY',
                'postal_code' => '11375',
                'latitude' => 40.7282,
                'longitude' => -73.8317,
                'services' => ['general', 'vaccination', 'dental', 'surgery'],
                'accepted_species' => ['dog', 'cat'],
                'specialization' => 'Preventive Care',
                'consultation_fee' => 1100,
                'home_visit_fee' => 1700,
                'is_emergency_available' => true,
                'is_24_hours' => true,
                'vet_status' => 'approved',
                'verification_status' => 'approved',
                'is_active' => true,
            ],
            [
                'clinic_name' => 'Bronx Animal Care',
                'vet_name' => 'Dr. Lisa Park',
                'phone' => '+1-555-0105',
                'email' => 'info@bronxanimalcare.com',
                'address' => '555 Grand Concourse, Bronx',
                'city' => 'Bronx',
                'state' => 'NY',
                'postal_code' => '10451',
                'latitude' => 40.8176,
                'longitude' => -73.9219,
                'services' => ['general', 'emergency', 'x-ray', 'lab'],
                'accepted_species' => ['dog', 'cat', 'rabbit', 'fish'],
                'specialization' => 'Emergency Response',
                'consultation_fee' => 1300,
                'home_visit_fee' => 2000,
                'is_emergency_available' => true,
                'is_24_hours' => false,
                'vet_status' => 'pending',
                'verification_status' => 'pending',
                'is_active' => true,
            ],
        ];

        foreach ($vets as $vetData) {
            // DatabaseSeeder disables model events; set UUID explicitly for new rows.
            $vet = VetProfile::withTrashed()->firstOrNew(['email' => $vetData['email']]);

            // Split protected (admin-gated) fields out so $fillable hardening doesn't silently drop them.
            $protectedFields = array_intersect_key(
                $vetData,
                array_flip(['vet_status', 'verification_status', 'is_active'])
            );
            $fillableData = array_diff_key($vetData, $protectedFields);

            $vet->fill($fillableData);
            $vet->forceFill($protectedFields);

            if (empty($vet->uuid)) {
                $vet->uuid = (string) Str::uuid();
            }

            if ($vet->trashed()) {
                $vet->restore();
            }

            $vet->save();

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
