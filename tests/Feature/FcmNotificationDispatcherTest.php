<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VetProfile;
use App\Services\FcmNotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Factory as FirebaseFactory;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Tests\TestCase;

class FcmNotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private FcmNotificationDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Firebase factory and messaging service
        $factoryMock = \Mockery::mock(FirebaseFactory::class);
        $messagingMock = \Mockery::mock(\Kreait\Firebase\Messaging::class);

        $factoryMock->shouldReceive('createMessaging')
            ->andReturn($messagingMock)
            ->byDefault();

        $this->app->instance(FirebaseFactory::class, $factoryMock);
        $this->dispatcher = $this->app->make(FcmNotificationDispatcher::class);
    }

    /** @test */
    public function it_sends_push_notification_successfully()
    {
        $user = User::factory()->create(['fcm_token' => 'valid-token']);

        $messagingMock = \Mockery::mock(\Kreait\Firebase\Messaging::class);
        $reportMock = \Mockery::mock(\Kreait\Firebase\Messaging\MulticastSendReport::class);

        $messagingMock->shouldReceive('send')
            ->once()
            ->andReturn($reportMock);

        $reportMock->shouldReceive('isSuccess')
            ->once()
            ->andReturn(true);

        $this->app->instance(
            FirebaseFactory::class,
            \Mockery::mock(FirebaseFactory::class, [
                'createMessaging' => $messagingMock,
            ])
        );

        $result = $this->dispatcher->sendPush(
            $user,
            'Test Title',
            'Test Body',
            ['key' => 'value']
        );

        $this->assertTrue($result);
    }

    /** @test */
    public function it_clears_invalid_fcm_token_on_not_found()
    {
        $user = User::factory()->create(['fcm_token' => 'invalid-token']);

        $messagingMock = \Mockery::mock(\Kreait\Firebase\Messaging::class);
        $messagingMock->shouldReceive('send')
            ->once()
            ->andThrow(new NotFound('Token not found'));

        $this->app->instance(
            FirebaseFactory::class,
            \Mockery::mock(FirebaseFactory::class, [
                'createMessaging' => $messagingMock,
            ])
        );

        $result = $this->dispatcher->sendPush($user, 'Title', 'Body');

        $this->assertFalse($result);
        $this->assertNull($user->fresh()->fcm_token);
    }

    /** @test */
    public function it_skips_send_when_no_fcm_token()
    {
        $user = User::factory()->create(['fcm_token' => null]);

        $result = $this->dispatcher->sendPush($user, 'Title', 'Body');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_sends_email_notification()
    {
        $user = User::factory()->create();

        \Mail::fake();

        $result = $this->dispatcher->sendEmail(
            $user,
            'Test Subject',
            'test-template',
            ['data' => 'value']
        );

        $this->assertTrue($result);
        \Mail::assertSent(\Illuminate\Mail\Message::class);
    }

    /** @test */
    public function it_handles_email_failure()
    {
        $user = User::factory()->create();

        \Mail::shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('Mail failed'));

        // Need to re-bind since Mail is already set up
        $result = $this->dispatcher->sendEmail($user, 'Subject', 'template');

        $this->assertFalse($result);
    }
}
