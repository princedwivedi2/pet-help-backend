<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'type', 'price',
        'duration_days', 'features', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_days' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
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

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
