<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AdBanner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'image_url', 'link_url', 'position',
        'is_active', 'priority', 'starts_at', 'ends_at',
        'impressions', 'clicks',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'impressions' => 'integer',
            'clicks' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeForPosition($query, string $position)
    {
        return $query->where('position', $position);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
