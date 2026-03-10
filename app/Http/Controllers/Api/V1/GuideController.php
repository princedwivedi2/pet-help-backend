<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Guide\IndexGuideRequest;
use App\Models\EmergencyCategory;
use App\Models\EmergencyGuide;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $perPage = min((int) ($request->per_page ?? 20), 50);

        $guides = $query->orderBy('title')
            ->paginate($perPage, [
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
            'guides'     => $guides->items(),
            'pagination' => [
                'current_page' => $guides->currentPage(),
                'last_page'    => $guides->lastPage(),
                'per_page'     => $guides->perPage(),
                'total'        => $guides->total(),
            ],
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

    // ─── Admin: Emergency Category CRUD ─────────────────────────────

    public function adminCategories(Request $request): JsonResponse
    {
        $categories = EmergencyCategory::ordered()->withCount('guides')->get();

        return $this->success('All categories retrieved', ['categories' => $categories]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'icon'        => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'sometimes|boolean',
        ]);

        $data = $request->only(['name', 'icon', 'description', 'sort_order', 'is_active']);
        $data['slug'] = \Illuminate\Support\Str::slug($request->name);

        $category = EmergencyCategory::create($data);

        return $this->created('Category created', ['category' => $category]);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $category = EmergencyCategory::find($id);

        if (!$category) {
            return $this->notFound('Category not found');
        }

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'icon'        => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'sort_order'  => 'nullable|integer|min:0',
            'is_active'   => 'sometimes|boolean',
        ]);

        $data = $request->only(['name', 'icon', 'description', 'sort_order', 'is_active']);
        if ($request->filled('name')) {
            $data['slug'] = \Illuminate\Support\Str::slug($request->name);
        }

        $category->update($data);

        return $this->success('Category updated', ['category' => $category]);
    }

    public function destroyCategory(int $id): JsonResponse
    {
        $category = EmergencyCategory::find($id);

        if (!$category) {
            return $this->notFound('Category not found');
        }

        $category->delete();

        return $this->success('Category deleted');
    }

    // ─── Admin: Emergency Guide CRUD ────────────────────────────────

    public function adminIndex(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->per_page ?? 20), 50);

        $query = EmergencyGuide::with('category:id,name,slug');

        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        $guides = $query->orderBy('title')->paginate($perPage);

        return $this->success('All guides retrieved', [
            'guides' => $guides->items(),
            'pagination' => [
                'current_page' => $guides->currentPage(),
                'last_page'    => $guides->lastPage(),
                'per_page'     => $guides->perPage(),
                'total'        => $guides->total(),
            ],
        ]);
    }

    public function storeGuide(Request $request): JsonResponse
    {
        $request->validate([
            'category_id'            => 'required|exists:emergency_categories,id',
            'title'                  => 'required|string|max:255',
            'summary'                => 'nullable|string|max:1000',
            'content'                => 'required|string',
            'applicable_species'     => 'nullable|array',
            'severity_level'         => 'nullable|string|in:low,medium,high,critical',
            'estimated_read_minutes' => 'nullable|integer|min:1',
            'is_published'           => 'sometimes|boolean',
        ]);

        $data = $request->only([
            'category_id', 'title', 'summary', 'content',
            'applicable_species', 'severity_level', 'estimated_read_minutes', 'is_published',
        ]);
        $data['slug'] = \Illuminate\Support\Str::slug($request->title);

        $guide = EmergencyGuide::create($data);

        return $this->created('Guide created', ['guide' => $guide->load('category:id,name,slug')]);
    }

    public function updateGuide(Request $request, int $id): JsonResponse
    {
        $guide = EmergencyGuide::find($id);

        if (!$guide) {
            return $this->notFound('Guide not found');
        }

        $request->validate([
            'category_id'            => 'sometimes|exists:emergency_categories,id',
            'title'                  => 'sometimes|string|max:255',
            'summary'                => 'nullable|string|max:1000',
            'content'                => 'sometimes|string',
            'applicable_species'     => 'nullable|array',
            'severity_level'         => 'nullable|string|in:low,medium,high,critical',
            'estimated_read_minutes' => 'nullable|integer|min:1',
            'is_published'           => 'sometimes|boolean',
        ]);

        $data = $request->only([
            'category_id', 'title', 'summary', 'content',
            'applicable_species', 'severity_level', 'estimated_read_minutes', 'is_published',
        ]);
        if ($request->filled('title')) {
            $data['slug'] = \Illuminate\Support\Str::slug($request->title);
        }

        $guide->update($data);

        return $this->success('Guide updated', ['guide' => $guide->load('category:id,name,slug')]);
    }

    public function destroyGuide(int $id): JsonResponse
    {
        $guide = EmergencyGuide::find($id);

        if (!$guide) {
            return $this->notFound('Guide not found');
        }

        $guide->delete();

        return $this->success('Guide deleted');
    }
}
