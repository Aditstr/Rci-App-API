<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements MustVerifyEmail, FilamentUser
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
        'google_id',
        'email_verified_at',
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

    public function complianceFlags(): HasMany
    {
        return $this->hasMany(ComplianceFlag::class, 'subject_user_id');
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

    /**
     * Determine if the user has verified their email address.
     * Google OAuth users are automatically considered verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return ! is_null($this->email_verified_at) || ! is_null($this->google_id);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Determine if the user can access the Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin() && $this->is_active;
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
        if (in_array($this->role ?? '', ['corporate', 'lawyer'])) {
            return true;
        }

        try {
            return $this->subscriptions()
                ->where('status', 'active')
                ->where('ends_at', '>', now())
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
