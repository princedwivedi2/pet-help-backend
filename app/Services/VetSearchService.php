<?php

namespace App\Services;

use App\Models\VetProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class VetSearchService
{
    private const EARTH_RADIUS_KM = 6371;

    public function getNearbyVets(
        float $latitude,
        float $longitude,
        float $radiusKm = 10,
        bool $emergencyOnly = false,
        bool $availableOnly = false,
        ?string $sortBy = 'distance',
        int $limit = 20
    ): Collection {
        $query = VetProfile::query()
            ->active()
            ->verified()
            ->select('vet_profiles.*')
            ->selectRaw($this->haversineFormula($latitude, $longitude) . ' AS distance_km')
            ->having('distance_km', '<=', $radiusKm);

        if ($emergencyOnly) {
            $query->emergencyAvailable();
        }

        if ($availableOnly) {
            $query->where(function ($q) {
                $q->where('is_24_hours', true)
                    ->orWhereHas('availabilities', function ($subQ) {
                        $dayOfWeek = now()->dayOfWeek;
                        $currentTime = now()->format('H:i:s');
                        $subQ->where('day_of_week', $dayOfWeek)
                            ->where('open_time', '<=', $currentTime)
                            ->where('close_time', '>=', $currentTime);
                    });
            });
        }

        $query = match ($sortBy) {
            'rating' => $query->orderByDesc('rating')->orderBy('distance_km'),
            'distance' => $query->orderBy('distance_km'),
            default => $query->orderBy('distance_km'),
        };

        return $query->limit($limit)->get();
    }

    public function findByUuid(string $uuid): ?VetProfile
    {
        return VetProfile::where('uuid', $uuid)
            ->active()
            ->verified()
            ->with('availabilities')
            ->first();
    }

    private function haversineFormula(float $latitude, float $longitude): string
    {
        // Haversine formula for calculating distance between two points
        return sprintf(
            '(%d * ACOS(
                COS(RADIANS(%f)) * COS(RADIANS(latitude)) *
                COS(RADIANS(longitude) - RADIANS(%f)) +
                SIN(RADIANS(%f)) * SIN(RADIANS(latitude))
            ))',
            self::EARTH_RADIUS_KM,
            $latitude,
            $longitude,
            $latitude
        );
    }

    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $latDiff = deg2rad($lat2 - $lat1);
        $lonDiff = deg2rad($lon2 - $lon1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDiff / 2) * sin($lonDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }
}
