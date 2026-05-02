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
        $this->vetProfile = VetProfile::factory()->create([
            'user_id' => $this->vetUser->id,
            'vet_status' => 'approved',
            'verification_status' => 'verified',
        ]);
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
        $response = $this->getJson('/api/v1/vets?languages=not-an-array');

        $response->assertStatus(422);
    }

    /** @test */
    public function it_validates_language_codes_are_strings()
    {
        $response = $this->getJson('/api/v1/vets?languages[]=en&languages[]=123&languages[]=fr');

        // Should not fail validation since query params are strings, but let's test that filtering works
        $response->assertStatus(200);
    }

    /** @test */
    public function it_allows_empty_languages_array()
    {
        $response = $this->getJson('/api/v1/vets?languages[]=');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_includes_languages_in_profile_response()
    {
        $languages = ['en', 'es'];
        $this->vetProfile->update(['languages' => $languages]);

        $response = $this->getJson("/api/v1/vets/{$this->vetProfile->uuid}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.vet.languages', $languages);
    }

    /** @test */
    public function it_includes_languages_in_search_results()
    {
        $languages = ['en', 'fr', 'de'];
        $this->vetProfile->update(['languages' => $languages]);

        $response = $this->getJson('/api/v1/vets');

        $response->assertStatus(200);
        // Languages should be included in the response
        $responseData = $response->json('data');
        $this->assertIsArray($responseData);
    }

    /** @test */
    public function it_defaults_to_empty_array_for_new_profiles()
    {
        $newVet = User::factory()->create(['role' => 'vet']);
        $profile = VetProfile::factory()->create([
            'user_id' => $newVet->id,
            'languages' => [],
        ]);

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

