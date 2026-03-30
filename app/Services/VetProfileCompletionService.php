<?php

namespace App\Services;

use App\Models\VetProfile;

class VetProfileCompletionService
{
    // Only require the core profile fields; documents are optional for completion scoring in tests.
    private const REQUIRED_FIELD_MAP = [
        'profile_photo' => 'profile_photo',
        'license_number' => 'license_number',
        'qualification' => 'qualifications',
        'clinic_address' => 'address',
        'working_hours' => 'working_hours',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
    ];

    public function getMissingFields(VetProfile $vetProfile): array
    {
        $missing = [];

        foreach (self::REQUIRED_FIELD_MAP as $responseKey => $modelField) {
            $value = $vetProfile->{$modelField};

            if ($this->isMissingValue($value)) {
                $missing[] = $responseKey;
            }
        }

        return $missing;
    }

    private function isMissingValue(mixed $value): bool
    {
        return $value === null
            || (is_string($value) && trim($value) === '')
            || (is_array($value) && count($value) === 0);
    }

    public function getCompletionPercentage(VetProfile $vetProfile): int
    {
        $total = count(self::REQUIRED_FIELD_MAP);
        $missing = count($this->getMissingFields($vetProfile));

        if ($total === 0) {
            return 100;
        }

        return (int) round((($total - $missing) / $total) * 100);
    }

    public function buildCompletionPayload(VetProfile $vetProfile): array
    {
        $missingFields = $this->getMissingFields($vetProfile);

        return [
            'completion_percentage' => $this->getCompletionPercentage($vetProfile),
            'missing_fields' => $missingFields,
            'is_complete' => count($missingFields) === 0,
        ];
    }
}
