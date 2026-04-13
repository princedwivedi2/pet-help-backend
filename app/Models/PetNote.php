<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PetNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pet_id',
        'user_id',
        'title',
        'content',
        'note_type',
        'mood_rating',
        'activity_level',
        'photo_urls',
        'tags',
        'reminder_at',
        'is_favorite',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'mood_rating' => 'integer',
            'activity_level' => 'integer', 
            'photo_urls' => 'array',
            'tags' => 'array',
            'reminder_at' => 'datetime',
            'is_favorite' => 'boolean',
            'is_private' => 'boolean',
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

    // Relationships
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('note_type', $type);
    }

    public function scopeWithReminders($query)
    {
        return $query->whereNotNull('reminder_at')
                    ->where('reminder_at', '>', now());
    }

    // Route key
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}