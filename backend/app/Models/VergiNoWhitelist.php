<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VergiNoWhitelist extends Model
{
    use HasFactory;

    protected $table = 'vergi_no_whitelist';

    protected $fillable = [
        'vergi_no',
        'company_name',
        'city',
        'district',
        'address',
        'is_active',
        'is_used',
        'used_by_user_id',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_used' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    public function scopeAvailable($query)
    {
        return $query->active()->unused();
    }

    public static function findByVergiNo(string $vergiNo): ?self
    {
        return static::where('vergi_no', $vergiNo)->first();
    }

    public static function findActive(string $vergiNo): ?self
    {
        return static::where('vergi_no', $vergiNo)->active()->first();
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->is_used;
    }

    public static function isAlreadyUsed(string $vergiNo): bool
    {
        return static::where('vergi_no', $vergiNo)->where('is_used', true)->exists();
    }

    public function markAsUsed(int $userId): bool
    {
        return $this->update([
            'is_used' => true,
            'used_by_user_id' => $userId,
            'used_at' => now(),
        ]);
    }

    public function release(): bool
    {
        return $this->update([
            'is_used' => false,
            'used_by_user_id' => null,
            'used_at' => null,
        ]);
    }
}
