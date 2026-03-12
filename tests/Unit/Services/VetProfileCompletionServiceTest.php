<?php

namespace Tests\Unit\Services;

use App\Models\VetProfile;
use App\Services\VetProfileCompletionService;
use Tests\TestCase;

class VetProfileCompletionServiceTest extends TestCase
{
    public function test_it_calculates_completion_percentage_and_missing_fields(): void
    {
        $service = new VetProfileCompletionService();

        $profile = new VetProfile([
            'profile_photo' => null,
            'license_number' => 'VET-12345',
            'qualifications' => 'BVSc',
            'address' => '',
            'working_hours' => [],
            'latitude' => 19.076090,
            'longitude' => 72.877426,
        ]);

        $payload = $service->buildCompletionPayload($profile);

        $this->assertSame(57, $payload['completion_percentage']);
        $this->assertSame(['profile_photo', 'clinic_address', 'working_hours'], $payload['missing_fields']);
        $this->assertFalse($payload['is_complete']);
    }

    public function test_it_returns_complete_for_fully_populated_profile(): void
    {
        $service = new VetProfileCompletionService();

        $profile = new VetProfile([
            'profile_photo' => 'https://cdn.example.com/photo.jpg',
            'license_number' => 'VET-12345',
            'qualifications' => 'BVSc',
            'address' => '123 Clinic Street',
            'working_hours' => [
                ['day_of_week' => 1, 'open_time' => '09:00', 'close_time' => '17:00'],
            ],
            'latitude' => 19.076090,
            'longitude' => 72.877426,
        ]);

        $payload = $service->buildCompletionPayload($profile);

        $this->assertSame(100, $payload['completion_percentage']);
        $this->assertSame([], $payload['missing_fields']);
        $this->assertTrue($payload['is_complete']);
    }
}
