<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Kupon yetkilendirme politikasi
 */
class CouponPolicy
{
    use HandlesAuthorization;

    /**
     * Kimlik dogrulanmis kullanicilar kuponlari listeleyebilir
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Kupon sahibi veya super admin goruntuleyebilir
     */
    public function view(User $user, Coupon $coupon): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $coupon->seller_id === $user->id;
    }

    /**
     * Satici yetkisine sahip kullanicilar kupon olusturabilir
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->canSell();
    }

    /**
     * Kupon sahibi veya super admin guncelleyebilir
     */
    public function update(User $user, Coupon $coupon): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $coupon->seller_id === $user->id;
    }

    /**
     * Kupon sahibi veya super admin silebilir
     */
    public function delete(User $user, Coupon $coupon): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $coupon->seller_id === $user->id;
    }
}
