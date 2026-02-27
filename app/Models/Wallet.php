<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    /**
     * The user who owns this wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All transactions in this wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // ──────────────────────────────────────────────
    // Helper Methods
    // ──────────────────────────────────────────────

    /**
     * Credit (add) funds to the wallet.
     */
    public function credit(string $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Debit (subtract) funds from the wallet.
     *
     * @throws \RuntimeException if insufficient balance
     */
    public function debit(string $amount): void
    {
        if (bccomp($this->balance, $amount, 2) < 0) {
            throw new \RuntimeException('Saldo tidak mencukupi.');
        }

        $this->decrement('balance', $amount);
    }
}
