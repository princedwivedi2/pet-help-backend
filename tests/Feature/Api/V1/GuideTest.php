<?php

namespace Tests\Feature\Api\V1;

use App\Models\EmergencyCategory;
use App\Models\EmergencyGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideTest extends TestCase
{
    use RefreshDatabase;

    // ═══════════════════════════════════════════════════════════════════════
    // CATEGORIES
    // ═══════════════════════════════════════════════════════════════════════

    public function test_list_emergency_categories(): void
    {
        EmergencyCategory::create([
            'name' => 'Injuries',
            'slug' => 'injuries',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/emergency-categories');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data.categories');
    }

    public function test_list_categories_excludes_inactive(): void
    {
        EmergencyCategory::create([
            'name' => 'Active',
            'slug' => 'active',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        EmergencyCategory::create([
            'name' => 'Inactive',
            'slug' => 'inactive',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->getJson('/api/v1/emergency-categories');

        $response->assertOk()
            ->assertJsonCount(1, 'data.categories');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // GUIDES
    // ═══════════════════════════════════════════════════════════════════════

    public function test_list_guides(): void
    {
        $cat = EmergencyCategory::create([
            'name' => 'First Aid',
            'slug' => 'first-aid',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        EmergencyGuide::create([
            'category_id' => $cat->id,
            'title' => 'Dog CPR',
            'slug' => 'dog-cpr',
            'summary' => 'How to perform CPR on a dog',
            'content' => 'Step 1...',
            'severity_level' => 'critical',
            'is_published' => true,
            'applicable_species' => ['dog'],
        ]);

        $response = $this->getJson('/api/v1/guides');

        $response->assertOk()
            ->assertJsonCount(1, 'data.guides');
    }

    public function test_list_guides_filter_by_category(): void
    {
        $cat1 = EmergencyCategory::create([
            'name' => 'Injuries',
            'slug' => 'injuries',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $cat2 = EmergencyCategory::create([
            'name' => 'Poisoning',
            'slug' => 'poisoning',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        EmergencyGuide::create([
            'category_id' => $cat1->id,
            'title' => 'Wound Care',
            'slug' => 'wound-care',
            'summary' => 'Basic wound care',
            'content' => 'Content...',
            'severity_level' => 'moderate',
            'is_published' => true,
        ]);
        EmergencyGuide::create([
            'category_id' => $cat2->id,
            'title' => 'Chocolate Poisoning',
            'slug' => 'chocolate-poisoning',
            'summary' => 'What to do',
            'content' => 'Content...',
            'severity_level' => 'critical',
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/v1/guides?category_id={$cat1->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.guides');
    }

    public function test_show_guide(): void
    {
        $cat = EmergencyCategory::create([
            'name' => 'First Aid',
            'slug' => 'first-aid',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $guide = EmergencyGuide::create([
            'category_id' => $cat->id,
            'title' => 'Snake Bite',
            'slug' => 'snake-bite',
            'summary' => 'Emergency snake bite care',
            'content' => 'Detailed content...',
            'severity_level' => 'critical',
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/v1/guides/{$guide->id}");

        $response->assertOk()
            ->assertJsonPath('data.guide.title', 'Snake Bite');
    }

    public function test_show_unpublished_guide_404(): void
    {
        $cat = EmergencyCategory::create([
            'name' => 'First Aid',
            'slug' => 'first-aid',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $guide = EmergencyGuide::create([
            'category_id' => $cat->id,
            'title' => 'Draft Guide',
            'slug' => 'draft-guide',
            'summary' => 'Draft',
            'content' => 'Draft content...',
            'severity_level' => 'low',
            'is_published' => false,
        ]);

        $response = $this->getJson("/api/v1/guides/{$guide->id}");

        $response->assertStatus(404);
    }

    public function test_show_nonexistent_guide_404(): void
    {
        $response = $this->getJson('/api/v1/guides/99999');

        $response->assertStatus(404);
    }
}
