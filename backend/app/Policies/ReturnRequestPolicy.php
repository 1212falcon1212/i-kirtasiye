<?php

namespace App\Policies;

use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReturnRequestPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the return request.
     */
    public function view(User $user, ReturnRequest $returnRequest): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $returnRequest->buyer_id === $user->id || $returnRequest->seller_id === $user->id;
    }

    /**
     * Determine whether the user can approve the return request.
     */
    public function approve(User $user, ReturnRequest $returnRequest): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $returnRequest->seller_id === $user->id;
    }

    /**
     * Determine whether the user can reject the return request.
     */
    public function reject(User $user, ReturnRequest $returnRequest): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $returnRequest->seller_id === $user->id;
    }
}
