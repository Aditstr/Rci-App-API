<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LegalCase extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'cases';

    protected $fillable = [
        'case_number',
        'client_id',
        'expert_id',
        'title',
        'description',
        'category',
        'status',
        'priority',
        'ai_complexity_score',
        'ai_estimated_cost',
        'ai_review_result',
        'submitted_at',
        'assigned_at',
        'completed_at',
        'is_marketplace',
        'proposed_fee',
        'fee_notes',
        'fee_structure',
        'quotation_status',
    ];

    protected function casts(): array
    {
        return [
            'ai_review_result' => 'array',
            'ai_complexity_score' => 'integer',
            'ai_estimated_cost' => 'decimal:2',
            'submitted_at' => 'datetime',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_marketplace' => 'boolean',
            'proposed_fee' => 'decimal:2',
            'fee_structure' => 'array',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * The client who submitted this case.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * The expert (paralegal/lawyer) assigned to this case.
     */
    public function expert(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expert_id');
    }

    /**
     * Documents attached to this case.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'case_id');
    }

    /**
     * Chat messages for this case.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'case_id');
    }

    /**
     * Payments related to this case.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'case_id');
    }

    /**
     * AI-matched experts for this case.
     */
    public function caseMatches(): HasMany
    {
        return $this->hasMany(CaseMatch::class, 'case_id');
    }

    // ──────────────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────────────

    /**
     * Generate a unique case number (e.g., RCI-20260214-00001).
     */
    public static function generateCaseNumber(): string
    {
        $date = now()->format('Ymd');
        $lastCase = static::withTrashed()
            ->where('case_number', 'like', "RCI-{$date}-%")
            ->orderByDesc('case_number')
            ->first();

        $sequence = $lastCase
            ? (int) substr($lastCase->case_number, -5) + 1
            : 1;

        return sprintf('RCI-%s-%05d', $date, $sequence);
    }
}
