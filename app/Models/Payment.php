<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payable_type',
        'payable_id',
        'user_id',
        'vet_profile_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'amount',
        'token_amount',
        'balance_due',
        'platform_fee',
        'commission_amount',
        'vet_payout_amount',
        'payment_model',
        'payment_mode',
        'payment_status',
        'currency',
        'failure_reason',
        'paid_at',
        'balance_collected_at',
        'refunded_at',
        'razorpay_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'token_amount' => 'integer',
            'balance_due' => 'integer',
            'platform_fee' => 'integer',
            'commission_amount' => 'integer',
            'vet_payout_amount' => 'integer',
            'paid_at' => 'datetime',
            'balance_collected_at' => 'datetime',
            'refunded_at' => 'datetime',
            'razorpay_response' => 'array',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vetProfile(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    public function isPaid(): bool
    {
        return in_array($this->payment_status, ['captured', 'paid']);
    }

    public function isPending(): bool
    {
        return in_array($this->payment_status, ['pending', 'created', 'authorized']);
    }

    public function scopePaid($query)
    {
        return $query->whereIn('payment_status', ['captured', 'paid']);
    }

    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->whereIn('payment_status', ['pending', 'created', 'authorized']);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
