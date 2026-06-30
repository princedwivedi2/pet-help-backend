<?php

namespace Tests\Feature;

use App\Contracts\FirestoreWriter;
use App\Models\ConsultationMessage;
use App\Models\ConsultationSession;
use App\Services\Chat\FirestoreChatBroadcaster;
use App\Services\Chat\GoogleFirestoreWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirestoreChatBroadcasterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_writes_message_to_session_messages_subcollection()
    {
        $session = ConsultationSession::factory()->create();
        $message = ConsultationMessage::create([
            'consultation_session_id' => $session->id,
            'sender_id' => $session->user_id,
            'sender_role' => 'user',
            'type' => 'text',
            'body' => 'hello firestore',
        ]);

        $writer = \Mockery::mock(FirestoreWriter::class);
        $writer->shouldReceive('setDocument')
            ->once()
            ->with(
                "consultations/{$session->uuid}/messages/{$message->id}",
                \Mockery::on(function ($payload) use ($message) {
                    return $payload['body'] === 'hello firestore'
                        && $payload['sender_role'] === 'user'
                        && $payload['id'] === $message->id
                        && array_key_exists('created_at', $payload);
                })
            );

        $broadcaster = new FirestoreChatBroadcaster(fn () => $writer);
        $broadcaster->broadcastMessage($message);
    }

    /** @test */
    public function it_is_a_no_op_when_writer_resolver_returns_null()
    {
        // Firestore unconfigured (e.g. no ext-grpc) — chat persistence still works.
        $broadcaster = new FirestoreChatBroadcaster(fn () => null);

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

    /** @test */
    public function it_swallows_writer_failures()
    {
        // Write fails (network, quota, whatever). Must NOT propagate.
        $writer = \Mockery::mock(FirestoreWriter::class);
        $writer->shouldReceive('setDocument')->once()
            ->andThrow(new \RuntimeException('firestore down'));

        $session = ConsultationSession::factory()->create();
        $message = ConsultationMessage::create([
            'consultation_session_id' => $session->id,
            'sender_id' => $session->user_id,
            'sender_role' => 'user',
            'type' => 'text',
            'body' => 'doomed',
        ]);

        $broadcaster = new FirestoreChatBroadcaster(fn () => $writer);
        $broadcaster->broadcastMessage($message);
        $this->assertTrue(true);
    }

    // ─── GoogleFirestoreWriter path-parsing ─────────────────────────────────

    /** @test */
    public function google_writer_rejects_paths_with_odd_segment_count()
    {
        $client = \Mockery::mock(\Google\Cloud\Firestore\FirestoreClient::class);
        $writer = new GoogleFirestoreWriter($client);

        $this->expectException(\InvalidArgumentException::class);
        $writer->setDocument('only-collection', ['x' => 1]);
    }

    /** @test */
    public function google_writer_rejects_empty_path()
    {
        $client = \Mockery::mock(\Google\Cloud\Firestore\FirestoreClient::class);
        $writer = new GoogleFirestoreWriter($client);

        $this->expectException(\InvalidArgumentException::class);
        $writer->setDocument('/', []);
    }
}
