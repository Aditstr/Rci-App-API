<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'specialization_tags',
        'experience_years',
        'bio',
        'is_verified',
        'rating',
        'successful_cases_count',
        'current_workload',
    ];

    protected function casts(): array
    {
        return [
            'specialization_tags' => 'array',
            'is_verified' => 'boolean',
            'rating' => 'decimal:2',
            'experience_years' => 'integer',
            'successful_cases_count' => 'integer',
            'current_workload' => 'integer',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
