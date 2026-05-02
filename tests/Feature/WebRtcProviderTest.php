<?php

namespace Tests\Feature;

use App\Models\ConsultationSession;
use App\Models\User;
use App\Services\Video\WebRtcProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Factory as FirebaseFactory;
use Tests\TestCase;

class WebRtcProviderTest extends TestCase
{
    use RefreshDatabase;

    private WebRtcProvider $provider;
    private $databaseMock;

    protected function setUp(): void
    {
        parent::setUp();

        $factoryMock = \Mockery::mock(FirebaseFactory::class);
        $this->databaseMock = \Mockery::mock(\Kreait\Firebase\Database::class);

        $factoryMock->shouldReceive('createDatabase')
            ->andReturn($this->databaseMock)
            ->byDefault();

        $this->app->instance(FirebaseFactory::class, $factoryMock);
        $this->provider = $this->app->make(WebRtcProvider::class);
    }

    /** @test */
    public function it_creates_a_webrtc_room()
    {
        $session = ConsultationSession::factory()->create();

        $refMock = \Mockery::mock(\Kreait\Firebase\Reference::class);
        $this->databaseMock->shouldReceive('getReference')
            ->with("/signaling/room-{$session->uuid}")
            ->andReturn($refMock);

        $refMock->shouldReceive('set')->once();

        $result = $this->provider->createRoom($session);

        $this->assertEquals('room-' . $session->uuid, $result['room_id']);
        $this->assertEquals('webrtc', $result['provider']);
        $this->assertArrayHasKey('metadata', $result);
        $this->assertArrayHasKey('signaling_path', $result['metadata']);
    }

    /** @test */
    public function it_generates_a_join_token()
    {
        $session = ConsultationSession::factory()->create();
        $userId = 42;

        $token = $this->provider->generateJoinToken($session, 'user', $userId);

        $this->assertNotEmpty($token);
        // Token should be in format: header.body.signature
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        // Decode and verify payload
        $payload = json_decode(base64_decode($parts[1]), true);
        $this->assertEquals('room-' . $session->uuid, $payload['room_id']);
        $this->assertEquals($userId, $payload['user_id']);
        $this->assertEquals('user', $payload['role']);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('nonce', $payload);
    }

    /** @test */
    public function it_destroys_a_webrtc_room()
    {
        $session = ConsultationSession::factory()->create();

        $refMock = \Mockery::mock(\Kreait\Firebase\Reference::class);
        $this->databaseMock->shouldReceive('getReference')
            ->andReturn($refMock);

        $refMock->shouldReceive('update')->once();
        $refMock->shouldReceive('remove')->once();

        $this->provider->destroyRoom($session);

        // No exception means it passed
        $this->assertTrue(true);
    }

    /** @test */
    public function it_returns_provider_name()
    {
        $this->assertEquals('webrtc', $this->provider->name());
    }

    /** @test */
    public function it_generates_different_tokens_for_different_users()
    {
        $session = ConsultationSession::factory()->create();

        $token1 = $this->provider->generateJoinToken($session, 'user', 1);
        $token2 = $this->provider->generateJoinToken($session, 'vet', 2);

        $this->assertNotEquals($token1, $token2);

        // Verify payloads are different
        $payload1 = json_decode(base64_decode(explode('.', $token1)[1]), true);
        $payload2 = json_decode(base64_decode(explode('.', $token2)[1]), true);

        $this->assertEquals(1, $payload1['user_id']);
        $this->assertEquals('user', $payload1['role']);
        $this->assertEquals(2, $payload2['user_id']);
        $this->assertEquals('vet', $payload2['role']);
    }
}
