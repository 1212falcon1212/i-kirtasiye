<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerWallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'seller_id',
        'balance',
        'pending_balance',
        'withdrawn_balance',
        'total_earned',
        'total_commission',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'withdrawn_balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_commission' => 'decimal:2',
    ];

    /**
     * Get the seller (user) for this wallet
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id')->orderByDesc('created_at');
    }

    /**
     * Get or create wallet for a seller
     */
    public static function getOrCreate(User $seller): self
    {
        return self::firstOrCreate(
            ['seller_id' => $seller->id],
            [
                'balance' => 0,
                'pending_balance' => 0,
                'withdrawn_balance' => 0,
                'total_earned' => 0,
                'total_commission' => 0,
            ]
        );
    }

    /**
     * Get total balance (available + pending)
     */
    public function getTotalBalanceAttribute(): float
    {
        return $this->balance + $this->pending_balance;
    }

    /**
     * Add to pending balance
     */
    public function addPendingBalance(float $amount): void
    {
        $this->increment('pending_balance', $amount);
        $this->increment('total_earned', $amount);
    }

    /**
     * Release pending balance to available
     */
    public function releasePendingToAvailable(float $amount): bool
    {
        if ($amount > $this->pending_balance) {
            return false;
        }

        $this->decrement('pending_balance', $amount);
        $this->increment('balance', $amount);
        return true;
    }

    /**
     * Withdraw from available balance
     */
    public function withdraw(float $amount): bool
    {
        if ($amount > $this->balance) {
            return false;
        }

        $this->decrement('balance', $amount);
        $this->increment('withdrawn_balance', $amount);
        return true;
    }

    /**
     * Add commission deduction
     */
    public function addCommission(float $amount): void
    {
        $this->increment('total_commission', $amount);
    }

    /**
     * Check if has sufficient balance for withdrawal
     */
    public function canWithdraw(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
