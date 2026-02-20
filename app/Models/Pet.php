<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'species',
        'breed',
        'birth_date',
        'weight_kg',
        'photo_url',
        'medical_notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'weight_kg' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sosRequests(): HasMany
    {
        return $this->hasMany(SosRequest::class);
    }

    public function incidentLogs(): HasMany
    {
        return $this->hasMany(IncidentLog::class);
    }
}
