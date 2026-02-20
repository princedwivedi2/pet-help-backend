<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CommunityReply extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'post_id',
        'user_id',
        'parent_id',
        'content',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────────

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(CommunityVote::class, 'votable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(CommunityReport::class, 'reportable');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
