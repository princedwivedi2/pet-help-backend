<?php

namespace Database\Seeders;

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
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@petsathi.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Create test vet user
        $vetUser = User::firstOrCreate(
            ['email' => 'vet@petsathi.com'],
            [
                'name' => 'Dr. Sarah Johnson',
                'password' => bcrypt('vet123'),
                'role' => 'vet',
                'email_verified_at' => now(),
            ]
        );

        // Create test user
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );

        // Run seeders
        $this->call([
            EmergencyCategorySeeder::class,
            EmergencyGuideSeeder::class,
            VetProfileSeeder::class,
        ]);

        // Assign vet profile to the vet user
        if ($vetUser) {
            $vetProfile = \App\Models\VetProfile::where('email', 'info@downtownpetemergency.com')->first();
            if ($vetProfile) {
                $vetProfile->update([
                    'user_id' => $vetUser->id,
                    'vet_status' => 'approved',
                    'verified_at' => now(),
                ]);
            }
        }
    }
}
