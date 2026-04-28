<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        // Super admin can view all orders
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Approved pharmacists can view orders
        return $user->isRetailer() && $user->isApproved();
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Super admin can view any order
        if ($user->isSuperAdmin()) {
            return true;
        }

        // User can view their own orders (as buyer)
        if ($order->user_id === $user->id) {
            return true;
        }

        // Seller can view orders containing their items
        return $order->items()->where('seller_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        // Only approved pharmacists can create orders
        return $user->isRetailer() && $user->isApproved();
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Super admin can update any order
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Seller can update order items they own (for shipping status, etc.)
        if ($order->items()->where('seller_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can confirm delivery.
     */
    public function confirmDelivery(User $user, Order $order): bool
    {
        // Super admin can confirm any order
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only the buyer can confirm delivery
        return $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // Check if order can be cancelled based on its status
        if (! $order->canBeCancelled()) {
            return false;
        }

        // Super admin can cancel any order
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Only the buyer can cancel their own order
        return $order->user_id === $user->id;
    }
}
