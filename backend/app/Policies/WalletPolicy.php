<?php

namespace App\Policies;

use App\Models\SellerBankAccount;
use App\Models\SellerWallet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WalletPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view wallet and transactions.
     */
    public function view(User $user, SellerWallet $wallet): bool
    {
        return $wallet->seller_id === $user->id;
    }

    /**
     * Determine whether the user can manage a bank account.
     */
    public function manageBankAccount(User $user, SellerBankAccount $bankAccount): bool
    {
        return $bankAccount->seller_id === $user->id;
    }

    /**
     * Determine whether the user can create payout requests.
     */
    public function createPayout(User $user): bool
    {
        return $user->isRetailer() && $user->isApproved();
    }
}
