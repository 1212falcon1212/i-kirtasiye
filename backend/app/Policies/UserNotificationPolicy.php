<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Kullanici bildirimi yetkilendirme politikasi
 */
class UserNotificationPolicy
{
    use HandlesAuthorization;

    /**
     * Kullanici sadece kendi bildirimlerini listeleyebilir
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Kullanici sadece kendi bildirimini goruntuleyebilir
     */
    public function view(User $user, UserNotification $notification): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $notification->user_id === $user->id;
    }

    /**
     * Kimlik dogrulanmis kullanici bildirim olusturabilir
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Kullanici sadece kendi bildirimini guncelleyebilir
     */
    public function update(User $user, UserNotification $notification): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $notification->user_id === $user->id;
    }

    /**
     * Kullanici sadece kendi bildirimini silebilir
     */
    public function delete(User $user, UserNotification $notification): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $notification->user_id === $user->id;
    }
}
