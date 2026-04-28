<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Urun yetkilendirme politikasi
 */
class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Urunleri herkes goruntuleyebilir
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Tek bir urunu herkes goruntuleyebilir
     */
    public function view(?User $user, Product $product): bool
    {
        return true;
    }

    /**
     * Sadece satici yetkisine sahip kullanicilar urun olusturabilir
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->canSell();
    }

    /**
     * Urunu olusturan satici veya super admin guncelleyebilir
     */
    public function update(User $user, Product $product): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $product->created_by_id === $user->id;
    }

    /**
     * Urunu olusturan satici veya super admin silebilir
     */
    public function delete(User $user, Product $product): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $product->created_by_id === $user->id;
    }
}
