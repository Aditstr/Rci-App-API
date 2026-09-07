<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    public const CATEGORIES = [
        'kritik' => 'Kritik',
        'saran' => 'Saran',
        'kendala_teknis' => 'Kendala teknis',
    ];

    public const STATUSES = [
        'baru' => 'Baru',
        'ditinjau' => 'Ditinjau',
        'selesai' => 'Selesai',
    ];

    protected $table = 'feedback';

    protected $fillable = [
        'user_id', 'category', 'message', 'email', 'status',
        'admin_notes', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
