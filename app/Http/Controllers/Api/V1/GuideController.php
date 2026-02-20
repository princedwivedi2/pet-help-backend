<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Guide\IndexGuideRequest;
use App\Models\EmergencyCategory;
use App\Models\EmergencyGuide;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class GuideController extends Controller
{
    use ApiResponse;

    public function categories(): JsonResponse
    {
        $categories = EmergencyCategory::active()
            ->ordered()
            ->get(['id', 'name', 'slug', 'icon', 'description']);

        return $this->success('Categories retrieved successfully', [
            'categories' => $categories,
        ]);
    }

    public function index(IndexGuideRequest $request): JsonResponse
    {
        $query = EmergencyGuide::published()
            ->with('category:id,name,slug');

        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        $guides = $query->orderBy('title')
            ->get([
                'id',
                'category_id',
                'title',
                'slug',
                'summary',
                'applicable_species',
                'severity_level',
                'estimated_read_minutes',
            ]);

        return $this->success('Guides retrieved successfully', [
            'guides' => $guides,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $guide = EmergencyGuide::published()
            ->with('category:id,name,slug')
            ->find($id);

        if (!$guide) {
            return $this->notFound('Guide not found', [
                'guide' => ['Guide not found or not published.'],
            ]);
        }

        return $this->success('Guide retrieved successfully', [
            'guide' => $guide,
        ]);
    }
}
