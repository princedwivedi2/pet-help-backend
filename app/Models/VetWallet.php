<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VetWallet extends Model
{
    protected $fillable = [
        'vet_profile_id',
        'balance',
        'total_earned',
        'total_paid_out',
        'pending_payout',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
            'total_earned' => 'integer',
            'total_paid_out' => 'integer',
            'pending_payout' => 'integer',
        ];
    }

    public function vetProfile(): BelongsTo
    {
        return $this->belongsTo(VetProfile::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'vet_profile_id', 'vet_profile_id');
    }
}
