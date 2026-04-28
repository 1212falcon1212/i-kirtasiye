<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Sepet yetkilendirme politikasi
 */
class CartPolicy
{
    use HandlesAuthorization;

    /**
     * Kullanici sadece kendi sepetlerini goruntuleyebilir
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Kullanici sadece kendi sepetini goruntuleyebilir
     */
    public function view(User $user, Cart $cart): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $cart->user_id === $user->id;
    }

    /**
     * Kimlik dogrulanmis kullanici sepet olusturabilir
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Kullanici sadece kendi sepetini guncelleyebilir
     */
    public function update(User $user, Cart $cart): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $cart->user_id === $user->id;
    }

    /**
     * Kullanici sadece kendi sepetini silebilir
     */
    public function delete(User $user, Cart $cart): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $cart->user_id === $user->id;
    }
}
