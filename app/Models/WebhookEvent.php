<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Idempotency record for incoming webhook events.
 *
 * UNIQUE(provider, event_id) prevents the same payload from being processed twice
 * when a provider retries (network blips, ack timeouts). The webhook handler
 * inserts a row before doing any side-effects; on duplicate-key error we know
 * it's a replay and short-circuit with 200 OK.
 */
class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_id',
        'event_type',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
