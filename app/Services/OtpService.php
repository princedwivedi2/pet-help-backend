<?php

namespace App\Services;

use App\Contracts\NotificationDispatcher;
use App\Contracts\OtpCodeGenerator;
use App\Models\OtpChallenge;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpService
{
    private const DEFAULT_PURPOSE = 'login';
    private const CODE_TTL_MINUTES = 10;
    private const SEND_COOLDOWN_SECONDS = 60;
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private NotificationDispatcher $notificationDispatcher,
        private OtpCodeGenerator $codeGenerator,
    ) {}

    public function send(string $identifier, ?string $channel = null, ?string $purpose = null): OtpChallenge
    {
        $identifier = $this->normalizeIdentifier($identifier);
        $channel = $this->resolveChannel($identifier, $channel);
        $purpose = $this->normalizePurpose($purpose);

        return DB::transaction(function () use ($identifier, $channel, $purpose) {
            $activeChallenge = OtpChallenge::where('identifier', $identifier)
                ->where('channel', $channel)
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->whereNull('locked_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($activeChallenge && $activeChallenge->last_sent_at?->gt(now()->subSeconds(self::SEND_COOLDOWN_SECONDS))) {
                throw ValidationException::withMessages([
                    'identifier' => ['Please wait before requesting another OTP.'],
                ]);
            }

            OtpChallenge::where('identifier', $identifier)
                ->where('channel', $channel)
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->update(['locked_at' => now()]);

            $code = $this->codeGenerator->generate();

            $challenge = OtpChallenge::create([
                'uuid' => Str::uuid()->toString(),
                'identifier' => $identifier,
                'channel' => $channel,
                'purpose' => $purpose,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
                'last_sent_at' => now(),
                'attempts' => 0,
                'max_attempts' => self::MAX_ATTEMPTS,
                'metadata' => [
                    'delivery' => $channel,
                ],
            ]);

            $this->deliverCode($identifier, $channel, $code, $purpose);

            return $challenge->fresh();
        });
    }

    public function verify(string $identifier, string $code, ?string $channel = null, ?string $purpose = null): OtpChallenge
    {
        $identifier = $this->normalizeIdentifier($identifier);
        $channel = $this->resolveChannel($identifier, $channel);
        $purpose = $this->normalizePurpose($purpose);

        return DB::transaction(function () use ($identifier, $code, $channel, $purpose) {
            $challenge = OtpChallenge::where('identifier', $identifier)
                ->where('channel', $channel)
                ->where('purpose', $purpose)
                ->whereNull('verified_at')
                ->whereNull('locked_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$challenge) {
                throw ValidationException::withMessages([
                    'code' => ['The OTP is invalid or has expired.'],
                ]);
            }

            if ($challenge->expires_at === null || $challenge->expires_at->isPast()) {
                $challenge->forceFill(['locked_at' => now()])->save();
                throw ValidationException::withMessages([
                    'code' => ['The OTP is invalid or has expired.'],
                ]);
            }

            if (!Hash::check($code, $challenge->code_hash)) {
                $challenge->increment('attempts');
                $challenge->forceFill(['last_sent_at' => $challenge->last_sent_at ?? now()]);

                if ($challenge->attempts >= $challenge->max_attempts) {
                    $challenge->forceFill(['locked_at' => now()])->save();
                } else {
                    $challenge->save();
                }

                throw ValidationException::withMessages([
                    'code' => ['The OTP is invalid or has expired.'],
                ]);
            }

            $challenge->forceFill([
                'verified_at' => now(),
                'locked_at' => now(),
            ])->save();

            return $challenge->fresh();
        });
    }

    private function deliverCode(string $identifier, string $channel, string $code, string $purpose): void
    {
        $message = "Your Pet Help OTP for {$purpose} is {$code}. It expires in " . self::CODE_TTL_MINUTES . ' minutes.';

        if ($channel === 'email') {
            $recipient = new User([
                'name' => 'Pet Help user',
                'email' => $identifier,
            ]);

            $this->notificationDispatcher->sendEmail(
                $recipient,
                'Your Pet Help OTP',
                'emails.otp',
                ['code' => $code, 'purpose' => $purpose, 'expiresMinutes' => self::CODE_TTL_MINUTES]
            );
            return;
        }

        $this->notificationDispatcher->sendSms($identifier, $message);
    }

    private function normalizeIdentifier(string $identifier): string
    {
        return trim($identifier);
    }

    private function normalizePurpose(?string $purpose): string
    {
        $purpose = trim((string) $purpose);
        return $purpose === '' ? self::DEFAULT_PURPOSE : $purpose;
    }

    private function resolveChannel(string $identifier, ?string $channel): string
    {
        if ($channel !== null && in_array($channel, ['email', 'sms'], true)) {
            return $channel;
        }

        return str_contains($identifier, '@') ? 'email' : 'sms';
    }
}