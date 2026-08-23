<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceFlag extends Model
{
    use HasFactory;

    public const TYPE_OFF_PLATFORM_PAYMENT = 'off_platform_payment';
    public const SOURCE_AUTOMATIC = 'automatic';
    public const SOURCE_USER_REPORT = 'user_report';

    protected $fillable = [
        'case_id',
        'message_id',
        'reporter_id',
        'subject_user_id',
        'type',
        'source',
        'severity',
        'risk_score',
        'matched_signals',
        'evidence_text',
        'reporter_notes',
        'status',
        'review_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_signals' => 'array',
            'evidence_text' => 'encrypted',
            'risk_score' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
