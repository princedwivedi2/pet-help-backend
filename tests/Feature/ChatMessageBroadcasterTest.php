<?php

namespace Tests\Feature;

use App\Contracts\ChatMessageBroadcaster;
use App\Models\ConsultationMessage;
use App\Models\ConsultationSession;
use App\Models\User;
use App\Services\Chat\FirebaseChatBroadcaster;
use App\Services\Chat\NullChatBroadcaster;
use App\Services\ConsultationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatMessageBroadcasterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function consultation_service_invokes_broadcaster_on_post_message()
    {
        // Spy broadcaster — verifies the service calls into it on every postMessage.
        $broadcasterSpy = \Mockery::mock(ChatMessageBroadcaster::class);
        $broadcasterSpy->shouldReceive('broadcastMessage')
            ->once()
            ->with(\Mockery::on(fn ($msg) => $msg instanceof ConsultationMessage && $msg->body === 'hi vet'));

        $this->app->instance(ChatMessageBroadcaster::class, $broadcasterSpy);

        $user = User::factory()->create(['role' => 'user']);
        $session = ConsultationSession::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        /** @var ConsultationService $service */
        $service = app(ConsultationService::class);
        $message = $service->postMessage($session, $user, 'hi vet');

        $this->assertEquals('hi vet', $message->body);
        $this->assertEquals('user', $message->sender_role);
    }

    /** @test */
    public function null_broadcaster_is_a_safe_default()
    {
        $broadcaster = new NullChatBroadcaster();
        $msg = new ConsultationMessage();
        // No exceptions, no return value — verifies the no-op is callable.
        $broadcaster->broadcastMessage($msg);
        $this->assertTrue(true);
    }

    /** @test */
    public function firebase_broadcaster_swallows_database_failures()
    {
        // Resolver throws → broadcaster catches and is a no-op.
        $broadcaster = new FirebaseChatBroadcaster(function () {
            throw new \RuntimeException('firebase down');
        });

        $session = ConsultationSession::factory()->create();
        $message = ConsultationMessage::create([
            'consultation_session_id' => $session->id,
            'sender_id' => $session->user_id,
            'sender_role' => 'user',
            'type' => 'text',
            'body' => 'hi',
        ]);

        // Must not throw — Firebase failure cannot break chat persistence.
        $broadcaster->broadcastMessage($message);
        $this->assertTrue(true);
    }

    /** @test */
    public function firebase_broadcaster_returns_no_op_when_resolver_returns_null()
    {
        // Verifies the graceful-degrade path: when Firebase is unconfigured the
        // broadcaster is a no-op and never throws, so chat persistence isn't blocked.
        $broadcaster = new FirebaseChatBroadcaster(fn () => null);

        $session = ConsultationSession::factory()->create();
        $message = ConsultationMessage::create([
            'consultation_session_id' => $session->id,
            'sender_id' => $session->user_id,
            'sender_role' => 'user',
            'type' => 'text',
            'body' => 'silent',
        ]);

        $broadcaster->broadcastMessage($message);
        $this->assertTrue(true);
    }
}
