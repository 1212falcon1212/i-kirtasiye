<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Degerlendirme yetkilendirme politikasi
 */
class ReviewPolicy
{
    use HandlesAuthorization;

    /**
     * Degerlendirmeleri herkes goruntuleyebilir
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Tek bir degerlendirmeyi herkes goruntuleyebilir
     */
    public function view(?User $user, Review $review): bool
    {
        return true;
    }

    /**
     * Kimlik dogrulanmis kullanicilar degerlendirme olusturabilir
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Degerlendirmeyi yazan kullanici veya super admin guncelleyebilir
     */
    public function update(User $user, Review $review): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $review->buyer_id === $user->id;
    }

    /**
     * Degerlendirmeyi yazan kullanici veya super admin silebilir
     */
    public function delete(User $user, Review $review): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $review->buyer_id === $user->id;
    }
}
