<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Marka yetkilendirme politikasi
 */
class BrandPolicy
{
    use HandlesAuthorization;

    /**
     * Markalari herkes goruntuleyebilir
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Tek bir markayi herkes goruntuleyebilir
     */
    public function view(?User $user, Brand $brand): bool
    {
        return true;
    }

    /**
     * Sadece super admin marka olusturabilir
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Sadece super admin marka guncelleyebilir
     */
    public function update(User $user, Brand $brand): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Sadece super admin marka silebilir
     */
    public function delete(User $user, Brand $brand): bool
    {
        return $user->isSuperAdmin();
    }
}
