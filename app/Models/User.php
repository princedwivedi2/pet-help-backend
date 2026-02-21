<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $guarded = [
        'id',
        'email_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function pets(): HasMany
    {
        return $this->hasMany(Pet::class);
    }

    public function sosRequests(): HasMany
    {
        return $this->hasMany(SosRequest::class);
    }

    public function incidentLogs(): HasMany
    {
        return $this->hasMany(IncidentLog::class);
    }

    public function vetProfile(): HasOne
    {
        return $this->hasOne(VetProfile::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }

    public function blogComments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function blogLikes(): HasMany
    {
        return $this->hasMany(BlogLike::class);
    }

    public function communityPosts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    public function communityReplies(): HasMany
    {
        return $this->hasMany(CommunityReply::class);
    }

    public function communityVotes(): HasMany
    {
        return $this->hasMany(CommunityVote::class);
    }

    public function activeSosRequest()
    {
        return $this->sosRequests()->active()->latest()->first();
    }

    public function canCreatePet(): bool
    {
        return $this->pets()->count() < 10;
    }

    // ─── Role helpers ─────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isVet(): bool
    {
        return $this->role === 'vet';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }
}
