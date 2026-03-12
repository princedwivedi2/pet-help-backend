<?php

namespace Tests\Unit\Http\Requests\Api\V1\Vet;

use App\Http\Requests\Api\V1\Vet\VetUpdateProfileRequest;
use Tests\TestCase;

class VetUpdateProfileRequestTest extends TestCase
{
    public function test_required_rules_exist_for_critical_onboarding_fields(): void
    {
        $rules = (new VetUpdateProfileRequest())->rules();

        $this->assertContains('required', $rules['profile_photo']);
        $this->assertContains('required', $rules['working_hours']);
        $this->assertContains('required', $rules['license_number']);
        $this->assertContains('required', $rules['qualification']);
        $this->assertContains('required', $rules['clinic_address']);
        $this->assertContains('required', $rules['latitude']);
        $this->assertContains('required', $rules['longitude']);
    }

    public function test_custom_required_messages_are_defined(): void
    {
        $messages = (new VetUpdateProfileRequest())->messages();

        $this->assertSame('Profile photo is required.', $messages['profile_photo.required']);
        $this->assertSame('Working hours are required.', $messages['working_hours.required']);
        $this->assertSame('License number is required.', $messages['license_number.required']);
        $this->assertSame('Qualification is required.', $messages['qualification.required']);
        $this->assertSame('Clinic address is required.', $messages['clinic_address.required']);
        $this->assertSame('Latitude is required.', $messages['latitude.required']);
        $this->assertSame('Longitude is required.', $messages['longitude.required']);
    }
}
