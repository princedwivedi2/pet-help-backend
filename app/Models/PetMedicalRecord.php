<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PetMedicalRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pet_id',
        'recorded_by_user_id',
        'recorded_by_vet_id',
        'visit_record_id',
        'record_type',
        'title',
        'description',
        'medicine_name',
        'medicine_dosage',
        'medicine_frequency',
        'medicine_duration',
        'attachment_url',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'date',
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

    // ─── Relationships ───────────────────────────────────────────────

    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class);
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function recordedByVet(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class, 'recorded_by_vet_id');
    }

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(VisitRecord::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    public function scopeForPet($query, int $petId)
    {
        return $query->where('pet_id', $petId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('record_type', $type);
    }

    public function scopeInDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('recorded_at', '>=', $from);
        }
        if ($to) {
            $query->where('recorded_at', '<=', $to);
        }
        return $query;
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    public function isMedicineRecord(): bool
    {
        return $this->record_type === 'medicine';
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
