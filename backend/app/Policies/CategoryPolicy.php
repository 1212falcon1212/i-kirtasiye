<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Kategori yetkilendirme politikasi
 */
class CategoryPolicy
{
    use HandlesAuthorization;

    /**
     * Kategorileri herkes goruntuleyebilir
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Tek bir kategoriyi herkes goruntuleyebilir
     */
    public function view(?User $user, Category $category): bool
    {
        return true;
    }

    /**
     * Sadece super admin kategori olusturabilir
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Sadece super admin kategori guncelleyebilir
     */
    public function update(User $user, Category $category): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Sadece super admin kategori silebilir
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->isSuperAdmin();
    }
}
