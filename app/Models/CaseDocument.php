<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'case_id',
        'uploaded_by',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'document_type',
        'ai_review_status',
        'ai_review_result',
    ];

    protected function casts(): array
    {
        return [
            'ai_review_result' => 'array',
            'file_size' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * The case this document belongs to.
     */
    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    /**
     * The user who uploaded this document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
