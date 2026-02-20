<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VetVerificationLog extends Model
{
    protected $fillable = [
        'uuid',
        'vet_profile_id',
        'admin_id',
        'action',
        'reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
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

    public function vetProfile(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
