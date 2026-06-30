<?php

namespace App\Services;

use App\Contracts\NotificationDispatcher;
use App\Models\DeviceToken;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

/**
 * FCM dispatcher using Firebase HTTP v1 API via the kreait Admin SDK.
 *
 * Multi-device fan-out:
 *   sendPush() reads ALL active rows from `device_tokens` for the user and sends
 *   one CloudMessage per token. Per-token failures (NotFound = invalid/expired)
 *   deactivate ONLY that row — other devices keep working. Returns true if any
 *   send succeeded.
 *
 * Falls back to the legacy `users.fcm_token` column when no device_tokens rows
 * exist yet (during the transition period after the multi-device migration).
 *
 * Constructor takes a Closure that lazily returns a Messaging instance (or null
 * when Firebase is unavailable).
 */
class FcmNotificationDispatcher implements NotificationDispatcher
{
    /** @var Closure(): ?Messaging */
    private Closure $messagingResolver;

    private ?Messaging $messaging = null;
    private bool $messagingResolved = false;

    /**
     * @param  Closure(): ?Messaging  $messagingResolver
     */
    public function __construct(Closure $messagingResolver)
    {
        $this->messagingResolver = $messagingResolver;
    }

    private function messaging(): ?Messaging
    {
        if (!$this->messagingResolved) {
            try {
                $this->messaging = ($this->messagingResolver)();
            } catch (\Throwable $e) {
                Log::warning('FCM messaging resolver failed', ['error' => $e->getMessage()]);
                $this->messaging = null;
            }
            $this->messagingResolved = true;
        }
        return $this->messaging;
    }

    /**
     * Send a push notification to every active device the user has registered.
     * Per-device failure deactivates that token; other devices are unaffected.
     *
     * @return bool true if at least one device successfully received the push.
     */
    public function sendPush(User $user, string $title, string $body, array $data = []): bool
    {
        $tokens = $this->resolveActiveTokens($user);
        if ($tokens->isEmpty()) {
            Log::debug('No active device tokens for user — push skipped', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        $messaging = $this->messaging();
        if ($messaging === null) {
            Log::warning('FCM unavailable — push skipped', [
                'user_id' => $user->id,
                'token_count' => $tokens->count(),
            ]);
            return false;
        }

        $notification = Notification::create($title, $body);
        $stringData = $this->coerceDataToStrings($data);
        $anySuccess = false;

        foreach ($tokens as $row) {
            $token = $row['token'];
            try {
                $message = CloudMessage::new()
                    ->withToken($token)
                    ->withNotification($notification)
                    ->withData($stringData);
                $messaging->send($message);
                $anySuccess = true;

                if ($row['device_token_id'] !== null) {
                    DeviceToken::where('id', $row['device_token_id'])
                        ->update(['last_seen_at' => now()]);
                }
            } catch (NotFound $e) {
                $this->deactivateToken($user, $row, 'fcm_not_found');
            } catch (\Throwable $e) {
                Log::warning('FCM send failed for one token (other devices unaffected)', [
                    'user_id' => $user->id,
                    'device_token_id' => $row['device_token_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $anySuccess;
    }

    /**
     * Returns rows of `[token, device_token_id|null]`. Falls back to the legacy
     * `users.fcm_token` column when no device_tokens rows exist yet — this lets
     * notifications keep working during the transition without forcing every
     * client to re-register first.
     *
     * @return \Illuminate\Support\Collection<int, array{token:string, device_token_id:?int}>
     */
    private function resolveActiveTokens(User $user): \Illuminate\Support\Collection
    {
        $rows = DeviceToken::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderByDesc('last_seen_at')
            ->get(['id', 'token'])
            ->map(fn (DeviceToken $dt) => [
                'token' => $dt->token,
                'device_token_id' => $dt->id,
            ]);

        if ($rows->isNotEmpty()) {
            return $rows;
        }

        // Legacy fallback: pre-migration users still have a single fcm_token.
        $legacy = $user->getRawOriginal('fcm_token')
            ?? User::where('id', $user->id)->value('fcm_token');

        if (!empty($legacy)) {
            return collect([
                ['token' => $legacy, 'device_token_id' => null],
            ]);
        }

        return collect();
    }

    private function deactivateToken(User $user, array $row, string $reason): void
    {
        Log::warning('FCM token invalid — deactivating', [
            'user_id' => $user->id,
            'device_token_id' => $row['device_token_id'],
            'reason' => $reason,
        ]);

        if ($row['device_token_id'] !== null) {
            DeviceToken::where('id', $row['device_token_id'])
                ->update(['is_active' => false]);
        } else {
            // Legacy single-column path — clear users.fcm_token if that's what failed.
            User::where('id', $user->id)
                ->where('fcm_token', $row['token'])
                ->update(['fcm_token' => null]);
        }
    }

    /**
     * FCM CloudMessage data values must be strings — coerce here.
     */
    private function coerceDataToStrings(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $out[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }
        return $out;
    }

    public function sendEmail(User $user, string $subject, string $template, array $data = []): bool
    {
        try {
            Mail::send($template, $data, function ($message) use ($user, $subject) {
                $message->to($user->email, $user->name)->subject($subject);
            });
            return true;
        } catch (\Throwable $e) {
            Log::error('Email notification failed', [
                'user_id' => $user->id,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendSms(string $phoneNumber, string $message): bool
    {
        Log::info('SMS notification stub called', [
            'phone'   => $phoneNumber,
            'message' => $message,
        ]);
        return false;
    }
}
