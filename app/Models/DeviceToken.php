<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-device FCM registration token.
 *
 * One row per (user, device). The `token` column is unique — the same token
 * cannot belong to two users (FCM tokens are device-scoped). When a token is
 * re-presented during registration we upsert + bump last_seen_at; if the token
 * previously belonged to another user, we re-assign it.
 */
class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'last_seen_at',
        'is_active',
    ];

    protected $hidden = [
        'token', // never expose raw tokens in API responses
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
