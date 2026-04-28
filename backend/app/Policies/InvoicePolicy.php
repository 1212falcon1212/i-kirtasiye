<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class InvoicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the invoice.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $invoice->seller_id === $user->id || $invoice->buyer_id === $user->id;
    }

    /**
     * Determine whether the user can download the invoice.
     */
    public function download(User $user, Invoice $invoice): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $invoice->seller_id === $user->id || $invoice->buyer_id === $user->id;
    }

    /**
     * Determine whether the user can create an invoice for an order.
     */
    public function createForOrder(User $user, Order $order): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $order->items()->where('seller_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can sync invoice to ERP.
     */
    public function syncToErp(User $user, Invoice $invoice): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $invoice->seller_id === $user->id;
    }
}
