<?php

namespace App\Models;

use App\Mail\PasswordResetMail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Role constants
     */
    public const ROLE_SUPER_ADMIN = 'super-admin';

    public const ROLE_RETAILER = 'retailer';

    public const ROLE_SELLER = 'seller';

    public const AVAILABLE_ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_RETAILER,
        self::ROLE_SELLER,
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'password',
        'vergi_no',
        'business_name',
        'nickname',
        'phone',
        'address',
        'city',
        'district',
        'city_id',
        'district_id',
        'trade_name',
        'kep_address',
        'mersis_no',
        'tax_number',
        'tax_office',
        'role',
        'is_verified',
        'verified_at',
        'verification_status',
        'rejection_reason',
        'documents',
        'approved_at',
        'approved_by',
        'contract_signed_at',
        'contract_ip',
        'contract_user_agent',
        'deactivated_at',
        'deactivation_reason',
        'seller_score',
        'seller_total_orders',
        'seller_review_count',
        'paytr_utoken',
        'fcm_token',
        'fcm_token_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'contract_signed_at' => 'datetime',
            'fcm_token_updated_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'documents' => 'array',
            'seller_score' => 'float',
        ];
    }

    /**
     * Verification status labels in Turkish
     */
    public const VERIFICATION_STATUS_LABELS = [
        'pending' => 'Onay Bekliyor',
        'approved' => 'Onaylandı',
        'rejected' => 'Reddedildi',
    ];

    /**
     * Şifre sıfırlama bildirimi için özel e-posta gönderir
     */
    public function sendPasswordResetNotification($token): void
    {
        Mail::to($this->email)->send(new PasswordResetMail($this, $token));
    }

    /**
     * Get the name for Filament panel
     */
    public function getFilamentName(): string
    {
        return $this->business_name ?? $this->email;
    }

    public function cityRel(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function districtRel(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    /**
     * Get display name (nickname if set, otherwise business_name)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->nickname ?: $this->business_name ?? $this->email;
    }

    /**
     * Check if user can access Filament panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is a retailer (kırtasiyeci - alıcı)
     */
    public function isRetailer(): bool
    {
        return $this->role === self::ROLE_RETAILER;
    }

    /**
     * Check if user is a seller / distributor (tedarikçi)
     */
    public function isSeller(): bool
    {
        return $this->role === self::ROLE_SELLER;
    }

    /**
     * Alias for seller (kullandığımız bağlantı tablosu "distributor" terminolojisi kullanıyor).
     */
    public function isDistributor(): bool
    {
        return $this->isSeller();
    }

    /**
     * Check if user can buy products.
     * - Retailers can always buy.
     * - Sellers can only buy from retailers they have approved links with.
     */
    public function canBuy(): bool
    {
        return $this->isRetailer();
    }

    /**
     * Check if user can buy from a specific seller.
     */
    public function canBuyFrom(User $seller): bool
    {
        if ($this->id === $seller->id) {
            return false;
        }

        if ($this->isRetailer()) {
            return true;
        }

        if ($this->isSeller()) {
            return $this->hasApprovedLinkWith($seller);
        }

        return false;
    }

    /**
     * Check if seller has an approved link with a retailer.
     */
    public function hasApprovedLinkWith(User $retailer): bool
    {
        if (! $this->isSeller() || ! $retailer->isRetailer()) {
            return false;
        }

        return $this->sentLinkRequests()
            ->where('retailer_id', $retailer->id)
            ->where('status', DistributorRetailerLink::STATUS_APPROVED)
            ->exists();
    }

    /**
     * Get link requests sent by this seller (distributor).
     */
    public function sentLinkRequests(): HasMany
    {
        return $this->hasMany(DistributorRetailerLink::class, 'distributor_id');
    }

    /**
     * Get link requests received by this retailer.
     */
    public function receivedLinkRequests(): HasMany
    {
        return $this->hasMany(DistributorRetailerLink::class, 'retailer_id');
    }

    public function approvedRetailerLinks(): HasMany
    {
        return $this->sentLinkRequests()->where('status', DistributorRetailerLink::STATUS_APPROVED);
    }

    public function approvedDistributorLinks(): HasMany
    {
        return $this->receivedLinkRequests()->where('status', DistributorRetailerLink::STATUS_APPROVED);
    }

    public function pendingLinkRequests(): HasMany
    {
        return $this->receivedLinkRequests()->where('status', DistributorRetailerLink::STATUS_PENDING);
    }

    /**
     * Both retailers and sellers can sell products.
     */
    public function canSell(): bool
    {
        return $this->isRetailer() || $this->isSeller();
    }

    /**
     * Retailer accounts require a vergi no.
     */
    public function requiresVergiNo(): bool
    {
        return $this->isRetailer();
    }

    public function isApproved(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'seller_id');
    }

    public function activeOffers(): HasMany
    {
        return $this->offers()->where('status', 'active');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(SellerWallet::class, 'seller_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(SellerBankAccount::class, 'seller_id');
    }

    public function defaultBankAccount(): HasOne
    {
        return $this->hasOne(SellerBankAccount::class, 'seller_id')->where('is_default', true);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function sellerOrderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'seller_id');
    }

    public function getVerificationStatusLabelAttribute(): string
    {
        return self::VERIFICATION_STATUS_LABELS[$this->verification_status] ?? $this->verification_status;
    }

    public function approve(int $approvedBy): void
    {
        $this->update([
            'verification_status' => 'approved',
            'is_verified' => true,
            'verified_at' => now(),
            'approved_at' => now(),
            'approved_by' => $approvedBy,
            'rejection_reason' => null,
        ]);
    }

    public function reject(string $reason, int $rejectedBy): void
    {
        $this->update([
            'verification_status' => 'rejected',
            'is_verified' => false,
            'rejection_reason' => $reason,
            'approved_by' => $rejectedBy,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    /**
     * Scope for retailers (kırtasiyeci) only.
     */
    public function scopeRetailers($query)
    {
        return $query->where('role', self::ROLE_RETAILER);
    }

    /**
     * Scope for sellers (tedarikçi) only.
     */
    public function scopeSellers($query)
    {
        return $query->where('role', self::ROLE_SELLER);
    }

    public function sellerDocuments(): HasMany
    {
        return $this->hasMany(SellerDocument::class);
    }

    public function documents(): HasMany
    {
        return $this->sellerDocuments();
    }

    public function hasRequiredDocuments(): bool
    {
        $requiredTypes = SellerDocument::REQUIRED_TYPES;
        $uploadedTypes = $this->sellerDocuments()->pluck('type')->toArray();

        return count(array_intersect($uploadedTypes, $requiredTypes)) === count($requiredTypes);
    }

    public function getDocumentsApprovedAttribute(): bool
    {
        $requiredTypes = SellerDocument::REQUIRED_TYPES;
        $approvedTypes = $this->sellerDocuments()
            ->where('status', 'approved')
            ->pluck('type')
            ->toArray();

        return count(array_intersect($approvedTypes, $requiredTypes)) === count($requiredTypes);
    }

    public function canAccessPlatform(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->documents_approved;
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(UserIntegration::class);
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'seller_id');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'seller_id');
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'seller_id');
    }

    public function reviewsGiven(): HasMany
    {
        return $this->hasMany(Review::class, 'buyer_id');
    }

    public function getSellerRatingAttribute(): array
    {
        return Review::getSellerRatings($this->id);
    }

    public function getSellerScoreAttribute(): ?float
    {
        return $this->attributes['seller_score'] ?? null;
    }

    public function canCreateCampaign(): bool
    {
        if ($this->seller_score === null) {
            return true;
        }

        return $this->seller_score >= 7;
    }
}
