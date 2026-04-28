<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerBankAccount extends Model
{
    use HasFactory;
    protected $fillable = [
        'seller_id',
        'bank_name',
        'iban',
        'account_holder',
        'swift_code',
        'tax_id',
        'tax_office',
        'kep_address',
        'mersis_number',
        'phone',
        'is_default',
        'is_verified',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
    ];

    /**
     * Get the seller
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Get masked IBAN (show only last 4 digits)
     */
    public function getMaskedIbanAttribute(): string
    {
        $length = strlen($this->iban);
        if ($length <= 4) {
            return $this->iban;
        }
        return str_repeat('*', $length - 4) . substr($this->iban, -4);
    }

    /**
     * Format IBAN with spaces
     */
    public function getFormattedIbanAttribute(): string
    {
        return trim(chunk_split($this->iban, 4, ' '));
    }

    /**
     * Scope for default account
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Make this account default
     */
    public function makeDefault(): void
    {
        // Remove default from other accounts
        self::where('seller_id', $this->seller_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }
}
