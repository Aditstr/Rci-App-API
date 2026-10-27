<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'idempotency_key',
        'plan_name',
        'price',
        'currency',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ──────────────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────────────

    /**
     * Active means status is active and the current time is inside
     * [starts_at, ends_at). The query scope below uses the same boundaries.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->starts_at !== null
            && $this->ends_at !== null
            && $this->starts_at->lte(now())
            && $this->ends_at->gt(now());
    }

    public function scopeActive(Builder $query): void
    {
        $now = now();

        $query->where('status', 'active')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now);
    }
}
