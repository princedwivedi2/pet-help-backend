<?php

namespace App\Services;

use App\Models\VetProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
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
        int $limit = 20,
        ?string $city = null,
        ?string $specialization = null,
        ?float $minRating = null
    ): Collection {
        $distanceExpr = $this->haversineFormula($latitude, $longitude);
        $isSqlite = DB::getDriverName() === 'sqlite';

        if ($isSqlite) {
            $latDelta = $radiusKm / 111;
            $cosLat = max(abs(cos(deg2rad($latitude))), 0.01);
            $lonDelta = $radiusKm / (111 * $cosLat);
        }

        $query = $this->baseApprovedQuery($emergencyOnly, $availableOnly, $specialization, $minRating)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($q) {
                $q->where('latitude', '!=', 0)
                  ->orWhere('longitude', '!=', 0);
            })
            ->select('vet_profiles.*')
            ->selectRaw($distanceExpr . ' AS distance_km');

        if ($isSqlite) {
            $query->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
                ->whereBetween('longitude', [$longitude - $lonDelta, $longitude + $lonDelta]);
            $query->whereRaw($distanceExpr . ' <= ?', [$radiusKm]);
        } else {
            $query->having('distance_km', '<=', $radiusKm);
        }

        if ($city) {
            $escapedCity = $this->escapeLike($city);
            $query->where(function ($q) use ($escapedCity) {
                $q->where('city', 'like', '%' . $escapedCity . '%')
                  ->orWhere('address', 'like', '%' . $escapedCity . '%');
            });
        }

        $query = match ($sortBy) {
            'distance' => $query->orderBy('distance_km'),
            'rating' => $query->orderByDesc('avg_rating')->orderBy('distance_km'),
            default => $query->orderBy('distance_km'),
        };

        return $query->limit($limit)->get();
    }

    /**
     * Return three discovery buckets so frontend can always render vets:
     * nearby vets, same-city vets, and all approved vets.
     */
    public function discoverApprovedVets(
        ?float $latitude,
        ?float $longitude,
        ?string $city = null,
        float $radiusKm = 10,
        bool $emergencyOnly = false,
        bool $availableOnly = false,
        ?string $specialization = null,
        ?float $minRating = null,
        int $limit = 30
    ): array {
        $limit = max(1, min($limit, 100));
        $base = $this->baseApprovedQuery($emergencyOnly, $availableOnly, $specialization, $minRating);

        $allQuery = (clone $base)->select('vet_profiles.*');
        if ($latitude !== null && $longitude !== null) {
            $allQuery->selectRaw($this->haversineFormula($latitude, $longitude) . ' AS distance_km')
                ->orderBy('distance_km');
        } else {
            $allQuery->orderByDesc('is_featured')->orderByDesc('avg_rating')->orderBy('vet_name');
        }
        $allVets = $allQuery->limit($limit)->get();

        $nearbyVets = collect();
        if ($latitude !== null && $longitude !== null) {
            $distanceExpr = $this->haversineFormula($latitude, $longitude);
            $isSqlite = DB::getDriverName() === 'sqlite';

            if ($isSqlite) {
                $latDelta = $radiusKm / 111;
                $cosLat = max(abs(cos(deg2rad($latitude))), 0.01);
                $lonDelta = $radiusKm / (111 * $cosLat);
            }

            $nearbyVets = (clone $base)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where(function ($q) {
                    $q->where('latitude', '!=', 0)
                      ->orWhere('longitude', '!=', 0);
                })
                ->select('vet_profiles.*')
                ->selectRaw($distanceExpr . ' AS distance_km')
                ->orderBy('distance_km')
                ->limit($limit);

            if ($isSqlite) {
                $nearbyVets->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
                    ->whereBetween('longitude', [$longitude - $lonDelta, $longitude + $lonDelta]);
                $nearbyVets->whereRaw($distanceExpr . ' <= ?', [$radiusKm]);
            } else {
                $nearbyVets->having('distance_km', '<=', $radiusKm);
            }

            $nearbyVets = $nearbyVets->get();
        }

        $cityName = trim((string) $city);
        $cityVets = collect();
        if ($cityName !== '') {
            $escapedCity = $this->escapeLike($cityName);
            $cityQuery = (clone $base)
                ->where(function ($q) use ($escapedCity) {
                    $q->where('city', 'like', '%' . $escapedCity . '%')
                        ->orWhere('address', 'like', '%' . $escapedCity . '%');
                })
                ->select('vet_profiles.*');

            if ($latitude !== null && $longitude !== null) {
                $cityQuery->selectRaw($this->haversineFormula($latitude, $longitude) . ' AS distance_km')
                    ->orderBy('distance_km');
            } else {
                $cityQuery->orderByDesc('avg_rating')->orderBy('vet_name');
            }

            $cityVets = $cityQuery->limit($limit)->get();
        }

        return [
            'nearby_vets' => $nearbyVets,
            'city_vets' => $cityVets,
            'all_vets' => $allVets,
        ];
    }

    public function findByUuid(string $uuid): ?VetProfile
    {
        return VetProfile::where('uuid', $uuid)
            ->active()
            ->verified()
            ->with(['availabilities', 'user:id,name'])
            ->first();
    }

    private function haversineFormula(float $latitude, float $longitude): string
    {
        $lat = number_format($latitude, 8, '.', '');
        $lng = number_format($longitude, 8, '.', '');
        $radius = self::EARTH_RADIUS_KM;

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite in CI lacks ACOS/RADIANS, so we fall back to a Manhattan-ish approximation.
            // Longitude degrees shrink as latitude increases, so this can be ~30-40% off around 45° lat.
            // Good enough for smoke tests; production drivers always use the proper haversine formula below.
            return "(
                (ABS(latitude - {$lat}) + ABS(longitude - {$lng})) * 111
            )";
        }

        // MySQL / PostgreSQL use LEAST/GREATEST
        return "(
            {$radius} * ACOS(
                LEAST(1, GREATEST(-1,
                    COS(RADIANS({$lat})) * COS(RADIANS(latitude)) *
                    COS(RADIANS(longitude) - RADIANS({$lng})) +
                    SIN(RADIANS({$lat})) * SIN(RADIANS(latitude))
                ))
            )
        )";
    }

    private function baseApprovedQuery(
        bool $emergencyOnly,
        bool $availableOnly,
        ?string $specialization,
        ?float $minRating
    ): Builder {
        $query = VetProfile::query()->active()->verified();

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

        if ($specialization) {
            $escaped = $this->escapeLike($specialization);
            $query->where(function ($q) use ($escaped, $specialization) {
                $q->where('specialization', 'like', '%' . $escaped . '%')
                    ->orWhere('qualifications', 'like', '%' . $escaped . '%')
                    ->orWhereJsonContains('services', $specialization);
            });
        }

        if ($minRating !== null) {
            $query->where('avg_rating', '>=', $minRating);
        }

        return $query;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $value);
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
