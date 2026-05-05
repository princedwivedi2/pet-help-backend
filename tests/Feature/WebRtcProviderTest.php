<?php

namespace Tests\Feature;

use App\Contracts\SignalingChannel;
use App\Models\ConsultationSession;
use App\Services\Video\WebRtcProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRtcProviderTest extends TestCase
{
    use RefreshDatabase;

    private WebRtcProvider $provider;
    /** @var \Mockery\MockInterface */
    private $channelMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->channelMock = \Mockery::mock(SignalingChannel::class);
        $this->provider = new WebRtcProvider(fn () => $this->channelMock);
    }

    /** @test */
    public function it_creates_a_webrtc_room()
    {
        $session = ConsultationSession::factory()->create();

        $this->channelMock->shouldReceive('initializeRoom')
            ->once()
            ->with("/signaling/room-{$session->uuid}", \Mockery::on(function ($metadata) use ($session) {
                return $metadata['session_id'] === $session->id
                    && $metadata['status'] === 'active'
                    && array_key_exists('offers', $metadata)
                    && array_key_exists('ice_candidates', $metadata);
            }));

        $result = $this->provider->createRoom($session);

        $this->assertEquals('room-' . $session->uuid, $result['room_id']);
        $this->assertEquals('webrtc', $result['provider']);
        $this->assertEquals("/signaling/room-{$session->uuid}", $result['metadata']['signaling_path']);
        $this->assertArrayNotHasKey('fallback', $result['metadata']);
    }

    /** @test */
    public function it_falls_back_when_signaling_resolver_returns_null()
    {
        $provider = new WebRtcProvider(fn () => null);
        $session = ConsultationSession::factory()->create();

        $result = $provider->createRoom($session);

        $this->assertEquals('room-' . $session->uuid, $result['room_id']);
        $this->assertTrue($result['metadata']['fallback']);
    }

    /** @test */
    public function it_falls_back_when_signaling_throws()
    {
        $session = ConsultationSession::factory()->create();
        $this->channelMock->shouldReceive('initializeRoom')->once()
            ->andThrow(new \RuntimeException('firebase down'));

        $result = $this->provider->createRoom($session);

        $this->assertTrue($result['metadata']['fallback']);
    }

    /** @test */
    public function it_generates_a_join_token()
    {
        $session = ConsultationSession::factory()->create();
        $token = $this->provider->generateJoinToken($session, 'user', 42);

        $parts = explode('.', $token);
        $this->assertCount(3, $parts);

        $payload = json_decode(base64_decode($parts[1]), true);
        $this->assertEquals('room-' . $session->uuid, $payload['room_id']);
        $this->assertEquals(42, $payload['user_id']);
        $this->assertEquals('user', $payload['role']);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('nonce', $payload);
    }

    /** @test */
    public function it_destroys_a_webrtc_room()
    {
        $session = ConsultationSession::factory()->create();
        $this->channelMock->shouldReceive('tearDownRoom')
            ->once()
            ->with("/signaling/room-{$session->uuid}");

        $this->provider->destroyRoom($session);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_swallows_errors_during_destroy()
    {
        $session = ConsultationSession::factory()->create();
        $this->channelMock->shouldReceive('tearDownRoom')->once()
            ->andThrow(new \RuntimeException('firebase down'));

        $this->provider->destroyRoom($session); // should not throw
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

        $payload1 = json_decode(base64_decode(explode('.', $token1)[1]), true);
        $payload2 = json_decode(base64_decode(explode('.', $token2)[1]), true);

        $this->assertEquals(1, $payload1['user_id']);
        $this->assertEquals('user', $payload1['role']);
        $this->assertEquals(2, $payload2['user_id']);
        $this->assertEquals('vet', $payload2['role']);
    }
}
