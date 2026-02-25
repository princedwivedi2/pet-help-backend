<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vet\SearchVetsRequest;
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
        $vets = $this->vetSearchService->getNearbyVets(
            latitude: $request->lat,
            longitude: $request->lng,
            radiusKm: $request->radius_km ?? 10,
            emergencyOnly: $request->boolean('emergency_only', false),
            availableOnly: $request->boolean('available_only', false),
            sortBy: $request->sort_by ?? 'distance',
            city: $request->city,
            specialization: $request->specialization,
            minRating: $request->min_rating ? (float) $request->min_rating : null,
        );

        $vetsData = $vets->map(function ($vet) {
            return [
                'uuid' => $vet->uuid,
                'clinic_name' => $vet->clinic_name,
                'vet_name' => $vet->vet_name,
                'phone' => $vet->phone,
                'address' => $vet->address,
                'city' => $vet->city,
                'latitude' => $vet->latitude,
                'longitude' => $vet->longitude,
                'is_emergency_available' => $vet->is_emergency_available,
                'is_24_hours' => $vet->is_24_hours,
                'is_verified' => $vet->is_verified,
                'rating' => $vet->rating,
                'review_count' => $vet->review_count,
                'distance_km' => round($vet->distance_km, 2),
            ];
        });

        return $this->success('Vets retrieved successfully', [
            'vets' => $vetsData,
            'search_params' => [
                'latitude' => $request->lat,
                'longitude' => $request->lng,
                'radius_km' => $request->radius_km ?? 10,
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

        return $this->success('Vet retrieved successfully', ['vet' => $vet]);
    }
}
