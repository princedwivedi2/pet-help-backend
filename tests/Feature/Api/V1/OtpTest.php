<?php

namespace Tests\Feature\Api\V1;

use App\Contracts\NotificationDispatcher;
use App\Contracts\OtpCodeGenerator;
use App\Models\OtpChallenge;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    private string $prefix = '/api/v1/auth';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(OtpCodeGenerator::class, new class implements OtpCodeGenerator {
            public function generate(): string
            {
                return '123456';
            }
        });

        $this->app->instance(NotificationDispatcher::class, new class implements NotificationDispatcher {
            public array $emails = [];
            public array $sms = [];

            public function sendPush(\App\Models\User $user, string $title, string $body, array $data = []): bool
            {
                return true;
            }

            public function sendEmail(\App\Models\User $user, string $subject, string $template, array $data = []): bool
            {
                $this->emails[] = compact('user', 'subject', 'template', 'data');
                return true;
            }

            public function sendSms(string $phoneNumber, string $message): bool
            {
                $this->sms[] = compact('phoneNumber', 'message');
                return true;
            }
        });
    }

    public function test_can_send_and_verify_email_otp(): void
    {
        $response = $this->postJson("{$this->prefix}/otp/send", [
            'identifier' => 'otp-user@example.com',
            'channel' => 'email',
            'purpose' => 'login',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonPath('data.purpose', 'login');

        $challenge = OtpChallenge::firstOrFail();
        $this->assertSame('otp-user@example.com', $challenge->identifier);

        $verify = $this->postJson("{$this->prefix}/otp/verify", [
            'identifier' => 'otp-user@example.com',
            'channel' => 'email',
            'purpose' => 'login',
            'code' => '123456',
        ]);

        $verify->assertOk()->assertJsonPath('data.verified', true);
        $this->assertNotNull($challenge->fresh()->verified_at);
    }

    public function test_can_send_and_verify_sms_otp(): void
    {
        $this->postJson("{$this->prefix}/otp/send", [
            'identifier' => '+15551234567',
            'channel' => 'sms',
            'purpose' => 'login',
        ])->assertCreated();

        $this->postJson("{$this->prefix}/otp/verify", [
            'identifier' => '+15551234567',
            'channel' => 'sms',
            'purpose' => 'login',
            'code' => '123456',
        ])->assertOk();
    }

    public function test_verify_rejects_wrong_code(): void
    {
        $this->postJson("{$this->prefix}/otp/send", [
            'identifier' => 'otp-wrong@example.com',
            'channel' => 'email',
        ])->assertCreated();

        $this->postJson("{$this->prefix}/otp/verify", [
            'identifier' => 'otp-wrong@example.com',
            'channel' => 'email',
            'code' => '000000',
        ])->assertStatus(422);
    }

    public function test_send_route_is_throttled(): void
    {
        Cache::flush();

        $throttled = false;

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson("{$this->prefix}/otp/send", [
                'identifier' => "throttle{$i}@example.com",
                'channel' => 'email',
            ]);

            if ($response->status() === 429) {
                $throttled = true;
                break;
            }

            $response->assertCreated();
        }

        $this->assertTrue($throttled, 'Expected otp/send to eventually rate-limit repeated requests.');
    }
}