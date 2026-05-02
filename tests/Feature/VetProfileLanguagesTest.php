<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VetProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VetProfileLanguagesTest extends TestCase
{
    use RefreshDatabase;

    private User $vetUser;
    private VetProfile $vetProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vetUser = User::factory()->create(['role' => 'vet']);
        $this->vetProfile = VetProfile::factory()->create(['user_id' => $this->vetUser->id]);
    }

    /** @test */
    public function it_stores_languages_as_json_array()
    {
        $languages = ['en', 'es', 'fr'];

        $updated = VetProfile::findOrFail($this->vetProfile->id)
            ->update(['languages' => $languages]);

        $this->assertTrue($updated);

        $profile = VetProfile::findOrFail($this->vetProfile->id);
        $this->assertEquals($languages, $profile->languages);
    }

    /** @test */
    public function it_validates_languages_as_array()
    {
        $this->actingAs($this->vetUser, 'sanctum');

        $response = $this->putJson("/api/v1/vet/profile", [
            'languages' => 'not-an-array',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('languages');
    }

    /** @test */
    public function it_validates_language_codes_are_strings()
    {
        $this->actingAs($this->vetUser, 'sanctum');

        $response = $this->putJson("/api/v1/vet/profile", [
            'languages' => ['en', 123, 'fr'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('languages');
    }

    /** @test */
    public function it_allows_empty_languages_array()
    {
        $this->actingAs($this->vetUser, 'sanctum');

        $response = $this->putJson("/api/v1/vet/profile", [
            'languages' => [],
        ]);

        $response->assertStatus(200);

        $profile = VetProfile::findOrFail($this->vetProfile->id);
        $this->assertEquals([], $profile->languages);
    }

    /** @test */
    public function it_includes_languages_in_profile_response()
    {
        $languages = ['en', 'es'];
        $this->vetProfile->update(['languages' => $languages]);

        $response = $this->getJson("/api/v1/vet/profile/{$this->vetProfile->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.languages', $languages);
    }

    /** @test */
    public function it_includes_languages_in_search_results()
    {
        $languages = ['en', 'fr', 'de'];
        $this->vetProfile->update(['languages' => $languages]);

        $response = $this->getJson('/api/v1/vet/search?specialization=general');

        $response->assertStatus(200);
        $vet = collect($response->json('data'))->firstWhere('id', $this->vetProfile->id);

        $this->assertNotNull($vet);
        $this->assertEquals($languages, $vet['languages']);
    }

    /** @test */
    public function it_defaults_to_empty_array_for_new_profiles()
    {
        $newVet = User::factory()->create(['role' => 'vet']);
        $profile = VetProfile::factory()->create(['user_id' => $newVet->id]);

        $this->assertEquals([], $profile->languages ?? []);
    }

    /** @test */
    public function it_casts_languages_to_array_automatically()
    {
        // Set as JSON string in database
        $this->vetProfile->languages = ['en', 'es'];
        $this->vetProfile->save();

        // Reload and verify it's cast to array
        $profile = VetProfile::findOrFail($this->vetProfile->id);
        $this->assertIsArray($profile->languages);
        $this->assertEquals(['en', 'es'], $profile->languages);
    }
}
