<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Setting;
use App\Models\SubOrder;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseHeldWalletBalances extends Command
{
    protected $signature = 'wallet:release-held-balances';

    protected $description = 'Release held wallet balances after payout hold period has passed';

    public function handle(WalletService $walletService, NotificationService $notificationService): int
    {
        $holdDays = (int) Setting::getValue('payment.payout_hold_days', 35);

        $this->info("Looking for sub_orders with buyer confirmation older than {$holdDays} days...");

        $subOrders = SubOrder::readyForWalletRelease($holdDays)
            ->with(['order', 'items'])
            ->get();

        if ($subOrders->isEmpty()) {
            $this->info('No sub_orders ready for balance release.');
            return self::SUCCESS;
        }

        $releasedCount = 0;

        foreach ($subOrders as $subOrder) {
            $seller = User::find($subOrder->seller_id);
            if (!$seller) continue;

            $released = $walletService->releasePendingBalance($seller, $subOrder->order, $subOrder->id);

            if ($released) {
                $releasedCount++;
                $notificationService->notifyWalletBalanceReleased($seller, $subOrder->order);
            }
        }

        $this->info("Released balances for {$releasedCount} sub_orders.");
        Log::info("wallet:release-held-balances — Released {$releasedCount} sub_order balances (hold: {$holdDays} days)");

        return self::SUCCESS;
    }
}
