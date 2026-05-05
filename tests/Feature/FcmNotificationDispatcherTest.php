<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FcmNotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Tests\TestCase;

class FcmNotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private function makeDispatcher(?Messaging $messaging): FcmNotificationDispatcher
    {
        return new FcmNotificationDispatcher(fn () => $messaging);
    }

    /** @test */
    public function it_sends_push_notification_successfully()
    {
        $user = User::factory()->create(['fcm_token' => 'valid-token']);
        $messaging = \Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')->once()->andReturn(['name' => 'projects/x/messages/y']);

        $dispatcher = $this->makeDispatcher($messaging);
        $this->assertTrue($dispatcher->sendPush($user, 'Title', 'Body', ['k' => 'v']));
    }

    /** @test */
    public function it_clears_invalid_fcm_token_on_not_found()
    {
        $user = User::factory()->create(['fcm_token' => 'invalid-token']);
        $messaging = \Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')->once()->andThrow(new NotFound('Token not found'));

        $dispatcher = $this->makeDispatcher($messaging);
        $this->assertFalse($dispatcher->sendPush($user, 'Title', 'Body'));
        $this->assertNull($user->fresh()->fcm_token);
    }

    /** @test */
    public function it_skips_send_when_no_fcm_token()
    {
        $user = User::factory()->create(['fcm_token' => null]);
        $dispatcher = $this->makeDispatcher(\Mockery::mock(Messaging::class));
        $this->assertFalse($dispatcher->sendPush($user, 'Title', 'Body'));
    }

    /** @test */
    public function it_skips_send_when_messaging_unavailable()
    {
        $user = User::factory()->create(['fcm_token' => 'some-token']);
        $dispatcher = $this->makeDispatcher(null);
        $this->assertFalse($dispatcher->sendPush($user, 'Title', 'Body'));
    }

    /** @test */
    public function it_returns_false_on_generic_send_failure()
    {
        $user = User::factory()->create(['fcm_token' => 'token']);
        $messaging = \Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')->once()->andThrow(new \RuntimeException('Network blip'));

        $dispatcher = $this->makeDispatcher($messaging);
        $this->assertFalse($dispatcher->sendPush($user, 'Title', 'Body'));
    }

    /** @test */
    public function it_sends_email_notification()
    {
        // Spy on Mail::send so we can verify the call without going through a Mailable
        // (the dispatcher uses raw template sends, which Mail::fake() doesn't surface).
        $user = User::factory()->create();
        Mail::shouldReceive('send')
            ->once()
            ->with('emails.otp', \Mockery::any(), \Mockery::any())
            ->andReturnNull();

        $dispatcher = $this->makeDispatcher(null);
        $result = $dispatcher->sendEmail(
            $user,
            'Subject',
            'emails.otp',
            ['code' => '123', 'purpose' => 'login', 'expiresMinutes' => 10]
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_handles_email_failure()
    {
        $user = User::factory()->create();
        Mail::shouldReceive('send')->once()->andThrow(new \Exception('Mail failed'));

        $dispatcher = $this->makeDispatcher(null);
        $this->assertFalse($dispatcher->sendEmail($user, 'Subject', 'template'));
    }

    // ─── Multi-device fan-out ───────────────────────────────────────────────

    /** @test */
    public function it_fans_out_push_to_every_active_device_token()
    {
        $user = User::factory()->create(['fcm_token' => null]);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-phone',  'platform' => 'android', 'is_active' => true]);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-tablet', 'platform' => 'android', 'is_active' => true]);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-web',    'platform' => 'web',     'is_active' => true]);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tok-old',    'platform' => 'ios',     'is_active' => false]);

        $messaging = \Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')->times(3); // 3 active tokens; inactive one skipped

        $dispatcher = $this->makeDispatcher($messaging);
        $this->assertTrue($dispatcher->sendPush($user, 'Title', 'Body'));
    }

    /** @test */
    public function invalid_token_only_deactivates_that_row_others_keep_working()
    {
        $user = User::factory()->create(['fcm_token' => null]);
        $bad  = DeviceToken::create(['user_id' => $user->id, 'token' => 'bad-tok',  'platform' => 'android', 'is_active' => true]);
        $good = DeviceToken::create(['user_id' => $user->id, 'token' => 'good-tok', 'platform' => 'web',     'is_active' => true]);

        $messaging = \Mockery::mock(Messaging::class);
        // First call (whichever token is processed first) throws NotFound;
        // the next call succeeds. Use a sequence based on token argument inspection.
        $messaging->shouldReceive('send')
            ->twice()
            ->andReturnUsing(function ($message) {
                $payload = $message->jsonSerialize();
                if (($payload['token'] ?? null) === 'bad-tok') {
                    throw new NotFound('not found');
                }
                return ['name' => 'projects/x/messages/y'];
            });

        $dispatcher = $this->makeDispatcher($messaging);
        $this->assertTrue($dispatcher->sendPush($user, 'Title', 'Body'));

        $this->assertFalse($bad->fresh()->is_active, 'bad token deactivated');
        $this->assertTrue($good->fresh()->is_active, 'good token still active');
    }

    /** @test */
    public function it_falls_back_to_legacy_users_fcm_token_when_no_device_tokens_rows()
    {
        // Pre-migration users with only the legacy single column should still work.
        $user = User::factory()->create(['fcm_token' => 'legacy-token']);
        $this->assertEquals(0, DeviceToken::where('user_id', $user->id)->count());

        $messaging = \Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')->once();

        $dispatcher = $this->makeDispatcher($messaging);
        $this->assertTrue($dispatcher->sendPush($user, 'Title', 'Body'));
    }

    /** @test */
    public function legacy_token_invalid_clears_users_fcm_token()
    {
        $user = User::factory()->create(['fcm_token' => 'legacy-bad']);
        $messaging = \Mockery::mock(Messaging::class);
        $messaging->shouldReceive('send')->once()->andThrow(new NotFound('not found'));

        $dispatcher = $this->makeDispatcher($messaging);
        $this->assertFalse($dispatcher->sendPush($user, 'Title', 'Body'));
        $this->assertNull($user->fresh()->fcm_token);
    }

    /** @test */
    public function it_returns_false_when_user_has_no_devices_at_all()
    {
        $user = User::factory()->create(['fcm_token' => null]);
        $dispatcher = $this->makeDispatcher(\Mockery::mock(Messaging::class));

        $this->assertFalse($dispatcher->sendPush($user, 'Title', 'Body'));
    }

    /** @test */
    public function it_returns_false_when_messaging_unavailable_even_with_devices()
    {
        $user = User::factory()->create();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'tok', 'platform' => 'web', 'is_active' => true]);

        $dispatcher = $this->makeDispatcher(null);
        $this->assertFalse($dispatcher->sendPush($user, 'Title', 'Body'));
    }
}
