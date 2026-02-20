<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CommunityPost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'topic_id',
        'user_id',
        'title',
        'content',
        'is_locked',
        'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'is_hidden' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────

    public function topic(): BelongsTo
    {
        return $this->belongsTo(CommunityTopic::class, 'topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommunityReply::class, 'post_id');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(CommunityVote::class, 'votable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(CommunityReport::class, 'reportable');
    }

    // ─── Scopes ─────────────────────────────────────────────────

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function scopeForTopic($query, int $topicId)
    {
        return $query->where('topic_id', $topicId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
