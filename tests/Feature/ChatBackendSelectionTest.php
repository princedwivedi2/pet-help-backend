<?php

namespace Tests\Feature;

use App\Contracts\ChatMessageBroadcaster;
use App\Services\Chat\FirebaseChatBroadcaster;
use App\Services\Chat\FirestoreChatBroadcaster;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Verifies the AppServiceProvider binding switches the broadcaster impl based
 * on FIREBASE_CHAT_BACKEND. Default = realtime DB; 'firestore' = Cloud Firestore.
 */
class ChatBackendSelectionTest extends TestCase
{
    /** @test */
    public function default_backend_is_realtime_database()
    {
        Config::set('services.firebase.chat_backend', 'realtime');

        $this->refreshApplication();
        Config::set('services.firebase.chat_backend', 'realtime');

        $broadcaster = app(ChatMessageBroadcaster::class);
        $this->assertInstanceOf(FirebaseChatBroadcaster::class, $broadcaster);
    }

    /** @test */
    public function firestore_backend_resolves_to_firestore_broadcaster()
    {
        $this->refreshApplication();
        Config::set('services.firebase.chat_backend', 'firestore');

        $broadcaster = app(ChatMessageBroadcaster::class);
        $this->assertInstanceOf(FirestoreChatBroadcaster::class, $broadcaster);
    }

    /** @test */
    public function unknown_backend_falls_back_to_realtime()
    {
        $this->refreshApplication();
        Config::set('services.firebase.chat_backend', 'mongodb-magic');

        $broadcaster = app(ChatMessageBroadcaster::class);
        $this->assertInstanceOf(FirebaseChatBroadcaster::class, $broadcaster,
            'unknown backend value should fall through to the realtime default');
    }
}
