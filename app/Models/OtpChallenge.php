<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OtpChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'channel',
        'purpose',
        'code_hash',
        'expires_at',
        'last_sent_at',
        'verified_at',
        'locked_at',
        'attempts',
        'max_attempts',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'verified_at' => 'datetime',
            'locked_at' => 'datetime',
            'metadata' => 'array',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $challenge) {
            if (empty($challenge->uuid)) {
                $challenge->uuid = Str::uuid()->toString();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'identifier', 'email');
    }

    public function isActive(): bool
    {
        return $this->verified_at === null
            && $this->locked_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }
}