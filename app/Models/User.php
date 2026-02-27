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
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar_url',
        'is_verified',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * Expert profile (for paralegals & lawyers).
     */
    public function expertProfile(): HasOne
    {
        return $this->hasOne(ExpertProfile::class);
    }

    /**
     * Wallet for this user.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Cases submitted by this user as a client.
     */
    public function clientCases(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'client_id');
    }

    /**
     * Cases assigned to this user as an expert (paralegal/lawyer).
     */
    public function assignedCases(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'expert_id');
    }

    /**
     * Chat messages sent by this user.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    /**
     * Subscriptions owned by this user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Payments made by this user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Documents uploaded by this user.
     */
    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'uploaded_by');
    }

    // ──────────────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isLawyer(): bool
    {
        return $this->role === 'lawyer';
    }

    public function isParalegal(): bool
    {
        return $this->role === 'paralegal';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isExpert(): bool
    {
        return in_array($this->role, ['paralegal', 'lawyer']);
    }

    public function isCorporate(): bool
    {
        return $this->role === 'corporate';
    }

    /**
     * Check if user has an active Pro subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();
    }
}
