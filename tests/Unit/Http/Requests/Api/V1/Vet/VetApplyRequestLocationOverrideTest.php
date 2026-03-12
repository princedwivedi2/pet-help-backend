<?php

namespace Tests\Unit\Http\Requests\Api\V1\Vet;

use App\Http\Requests\Api\V1\Vet\VetApplyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VetApplyRequestLocationOverrideTest extends TestCase
{
    public function test_location_override_confirmation_is_required_when_clinic_is_far(): void
    {
        $request = VetApplyRequest::create('/api/v1/vet/apply', 'POST', [
            'device_latitude' => 19.076090,
            'device_longitude' => 72.877426,
            'latitude' => 28.613900,
            'longitude' => 77.209000,
            'location_override_confirmed' => false,
        ]);

        $validator = Validator::make($request->all(), []);
        $request->withValidator($validator);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('location_override_confirmed', $validator->errors()->toArray());
    }

    public function test_location_override_not_required_when_clinic_is_near(): void
    {
        $request = VetApplyRequest::create('/api/v1/vet/apply', 'POST', [
            'device_latitude' => 19.076090,
            'device_longitude' => 72.877426,
            'latitude' => 19.082000,
            'longitude' => 72.880000,
            'location_override_confirmed' => false,
        ]);

        $validator = Validator::make($request->all(), []);
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    public function test_location_override_passes_when_explicitly_confirmed(): void
    {
        $request = VetApplyRequest::create('/api/v1/vet/apply', 'POST', [
            'device_latitude' => 19.076090,
            'device_longitude' => 72.877426,
            'latitude' => 28.613900,
            'longitude' => 77.209000,
            'location_override_confirmed' => true,
        ]);

        $validator = Validator::make($request->all(), []);
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }
}
