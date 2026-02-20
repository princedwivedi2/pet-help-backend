<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Pet\StorePetRequest;
use App\Http\Requests\Api\V1\Pet\UpdatePetRequest;
use App\Services\PetService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PetController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PetService $petService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pets = $this->petService->getUserPets($request->user());

        return $this->success('Pets retrieved successfully', ['pets' => $pets]);
    }

    public function store(StorePetRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->petService->canUserCreatePet($user)) {
            return $this->validationError('Maximum pets limit reached', [
                'pets' => ['You cannot have more than ' . PetService::MAX_PETS_PER_USER . ' pets.'],
            ]);
        }

        $pet = $this->petService->createPet($user, $request->validated());

        return $this->created('Pet created successfully', ['pet' => $pet]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $pet = $this->petService->findPetForUser($request->user(), $id);

        if (!$pet) {
            return $this->notFound('Pet not found', [
                'pet' => ['Pet not found or does not belong to you.'],
            ]);
        }

        return $this->success('Pet retrieved successfully', ['pet' => $pet]);
    }

    public function update(UpdatePetRequest $request, int $id): JsonResponse
    {
        $pet = $this->petService->findPetForUser($request->user(), $id);

        if (!$pet) {
            return $this->notFound('Pet not found', [
                'pet' => ['Pet not found or does not belong to you.'],
            ]);
        }

        $pet = $this->petService->updatePet($pet, $request->validated());

        return $this->success('Pet updated successfully', ['pet' => $pet]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $pet = $this->petService->findPetForUser($request->user(), $id);

        if (!$pet) {
            return $this->notFound('Pet not found', [
                'pet' => ['Pet not found or does not belong to you.'],
            ]);
        }

        $this->petService->deletePet($pet);

        return $this->success('Pet deleted successfully');
    }
}
