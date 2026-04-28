<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Kampanya yetkilendirme politikasi
 */
class CampaignPolicy
{
    use HandlesAuthorization;

    /**
     * Kimlik dogrulanmis kullanicilar kampanyalari listeleyebilir
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Kampanya sahibi veya super admin goruntuleyebilir
     */
    public function view(User $user, Campaign $campaign): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $campaign->seller_id === $user->id;
    }

    /**
     * Satici yetkisine sahip kullanicilar kampanya olusturabilir
     */
    public function create(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->canSell();
    }

    /**
     * Kampanya sahibi veya super admin guncelleyebilir
     */
    public function update(User $user, Campaign $campaign): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $campaign->seller_id === $user->id;
    }

    /**
     * Kampanya sahibi veya super admin silebilir
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $campaign->seller_id === $user->id;
    }
}
