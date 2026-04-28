<?php

namespace App\Filament\Widgets;

use App\Models\PayoutRequest;
use App\Models\SellerWallet;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Total platform balance (sum of all seller wallets)
        $totalBalance = SellerWallet::sum('balance');
        $totalPending = SellerWallet::sum('pending_balance');
        $totalCommission = SellerWallet::sum('total_commission');

        // Pending payout requests
        $pendingPayouts = PayoutRequest::pending()->count();
        $pendingPayoutAmount = PayoutRequest::pending()->sum('amount');

        // Today's orders
        $todayOrders = Order::whereDate('created_at', today())->count();
        $todayRevenue = Order::whereDate('created_at', today())->sum('total_amount');

        return [
            Stat::make('Toplam Platform Komisyonu', '₺' . number_format($totalCommission, 2, ',', '.'))
                ->description('Tüm zamanlar')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),

            Stat::make('Satıcı Bakiyeleri', '₺' . number_format($totalBalance + $totalPending, 2, ',', '.'))
                ->description('Çekilebilir: ₺' . number_format($totalBalance, 2, ',', '.'))
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),

            Stat::make('Bekleyen Ödemeler', $pendingPayouts . ' adet')
                ->description('Toplam: ₺' . number_format($pendingPayoutAmount, 2, ',', '.'))
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingPayouts > 0 ? 'warning' : 'gray'),

            Stat::make('Bugünkü Siparişler', $todayOrders . ' adet')
                ->description('Ciro: ₺' . number_format($todayRevenue, 2, ',', '.'))
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('info'),
        ];
    }
}
