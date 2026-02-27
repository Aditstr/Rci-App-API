<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_id',
        'expert_id',
        'match_score',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'match_score' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * The case this match belongs to.
     */
    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    /**
     * The expert (mitra) matched to this case.
     */
    public function expert(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expert_id');
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }
}
