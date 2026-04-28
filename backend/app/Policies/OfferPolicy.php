<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfferPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any offers.
     */
    public function viewAny(User $user): bool
    {
        // Super admin can view all offers
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Approved pharmacists can view offers
        return $user->isRetailer() && $user->isApproved();
    }

    /**
     * Determine whether the user can view the offer.
     */
    public function view(User $user, Offer $offer): bool
    {
        // Super admin can view any offer
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Approved pharmacists can view any offer (for purchasing)
        if ($user->isRetailer() && $user->isApproved()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create offers.
     */
    public function create(User $user): bool
    {
        // Only approved pharmacists can create offers
        return $user->isRetailer() && $user->isApproved();
    }

    /**
     * Determine whether the user can update the offer.
     */
    public function update(User $user, Offer $offer): bool
    {
        // Super admin can update any offer
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only the seller can update their own offer
        return $offer->seller_id === $user->id;
    }

    /**
     * Determine whether the user can delete the offer.
     */
    public function delete(User $user, Offer $offer): bool
    {
        // Super admin can delete any offer
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only the seller can delete their own offer
        return $offer->seller_id === $user->id;
    }
}
