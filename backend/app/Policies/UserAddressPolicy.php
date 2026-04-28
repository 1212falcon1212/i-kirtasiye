<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Kullanici adresi yetkilendirme politikasi
 */
class UserAddressPolicy
{
    use HandlesAuthorization;

    /**
     * Kullanici sadece kendi adreslerini listeleyebilir
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Kullanici sadece kendi adresini goruntuleyebilir
     */
    public function view(User $user, UserAddress $address): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $address->user_id === $user->id;
    }

    /**
     * Kimlik dogrulanmis kullanici adres olusturabilir
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Kullanici sadece kendi adresini guncelleyebilir
     */
    public function update(User $user, UserAddress $address): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $address->user_id === $user->id;
    }

    /**
     * Kullanici sadece kendi adresini silebilir
     */
    public function delete(User $user, UserAddress $address): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $address->user_id === $user->id;
    }
}
