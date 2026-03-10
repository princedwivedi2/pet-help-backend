<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vet\SearchVetsRequest;
use App\Models\VetProfile;
use App\Services\VetSearchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class VetController extends Controller
{
    use ApiResponse;

    public function __construct(
        private VetSearchService $vetSearchService
    ) {}

    public function index(SearchVetsRequest $request): JsonResponse
    {
        $authUser = $request->user();
        $city = $request->city ?? $authUser?->city;

        $buckets = $this->vetSearchService->discoverApprovedVets(
            latitude: $request->filled('lat') ? (float) $request->lat : null,
            longitude: $request->filled('lng') ? (float) $request->lng : null,
            city: $city,
            radiusKm: (float) ($request->radius_km ?? 10),
            emergencyOnly: $request->boolean('emergency_only', false),
            availableOnly: $request->boolean('available_only', false),
            specialization: $request->specialization,
            minRating: $request->min_rating ? (float) $request->min_rating : null,
            limit: (int) ($request->limit ?? 30),
        );

        $nearby = $buckets['nearby_vets']->map(fn ($vet) => $this->formatVet($vet, true))->values();
        $cityVets = $buckets['city_vets']->map(fn ($vet) => $this->formatVet($vet, true))->values();
        $all = $buckets['all_vets']->map(fn ($vet) => $this->formatVet($vet, true))->values();

        $legacyVets = $nearby->isNotEmpty() ? $nearby : ($cityVets->isNotEmpty() ? $cityVets : $all);

        return $this->success('Vets retrieved successfully', [
            'nearby_vets' => $nearby,
            'city_vets' => $cityVets,
            'all_vets' => $all,
            // Backward compatibility for old clients expecting a single `vets` list.
            'vets' => $legacyVets,
            'search_params' => [
                'latitude' => $request->lat,
                'longitude' => $request->lng,
                'radius_km' => $request->radius_km ?? 10,
                'city' => $city,
            ],
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        $vet = $this->vetSearchService->findByUuid($uuid);

        if (!$vet) {
            return $this->notFound('Vet not found', [
                'vet' => ['Vet profile not found or inactive.'],
            ]);
        }

        return $this->success('Vet retrieved successfully', [
            'vet' => $this->formatVet($vet, false),
        ]);
    }

    private function formatVet(VetProfile $vet, bool $isList = true): array
    {
        $data = [
            'uuid' => $vet->uuid,
            'clinic_name' => $vet->clinic_name,
            'vet_name' => $vet->vet_name,
            'phone' => $vet->phone,
            'email' => $vet->email,
            'address' => $vet->address,
            'state' => $vet->state,
            'city' => $vet->city,
            'postal_code' => $vet->postal_code,
            'latitude' => $vet->latitude,
            'longitude' => $vet->longitude,
            'services' => $vet->services ?? [],
            'accepted_species' => $vet->accepted_species ?? [],
            'is_emergency_available' => $vet->is_emergency_available,
            'is_24_hours' => $vet->is_24_hours,
            'is_verified' => $vet->vet_status === 'approved',
            'availability_status' => $vet->availability_status ?? 'offline',
            'profile_photo' => $vet->profile_photo,
            'consultation_fee' => $vet->consultation_fee,
            'home_visit_fee' => $vet->home_visit_fee,
            'specialization' => $vet->specialization,
            'consultation_types' => $vet->consultation_types ?? [],
            'is_featured' => (bool) $vet->is_featured,
            'avg_rating' => $vet->avg_rating,
            'total_reviews' => $vet->total_reviews,
            'completed_appointments' => $vet->completed_appointments ?? 0,
        ];

        if (isset($vet->distance_km)) {
            $data['distance_km'] = round((float) $vet->distance_km, 2);
        }
        if (!$isList) {
            $data['license_number'] = $vet->license_number;
            $data['qualifications'] = $vet->qualifications;
            $data['years_of_experience'] = $vet->years_of_experience;
            $data['availabilities'] = $vet->availabilities ?? [];
            $data['acceptance_rate'] = $vet->acceptance_rate;
            $data['avg_response_minutes'] = $vet->avg_response_minutes;
        }

        return $data;
    }
}
