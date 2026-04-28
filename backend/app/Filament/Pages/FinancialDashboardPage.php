<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\PayoutRequest;
use App\Models\SellerWallet;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinancialDashboardPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'filament.pages.financial-dashboard';

    protected static ?string $navigationLabel = 'Finansal Özet';

    protected static ?string $title = 'Finansal Dashboard';

    protected static ?string $navigationGroup = 'Finans';

    protected static ?int $navigationSort = 1;

    public function getStats(): array
    {
        $today = now()->startOfDay();
        $thisMonth = now()->startOfMonth();

        // Total sales
        $totalSales = Order::where('payment_status', 'paid')->sum('total_amount');
        $monthlySales = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $thisMonth)
            ->sum('total_amount');
        $dailySales = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $today)
            ->sum('total_amount');

        // Platform commission
        $totalCommission = Order::where('payment_status', 'paid')->sum('total_commission');
        $monthlyCommission = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $thisMonth)
            ->sum('total_commission');

        // Seller balances
        $totalSellerBalance = SellerWallet::sum('balance');
        $totalPendingBalance = SellerWallet::sum('pending_balance');

        // Payout requests
        $pendingPayouts = PayoutRequest::where('status', 'pending')->sum('amount');
        $pendingPayoutCount = PayoutRequest::where('status', 'pending')->count();
        $completedPayouts = PayoutRequest::where('status', 'completed')->sum('amount');

        // Users
        $pendingPharmacies = User::where('role', User::ROLE_RETAILER)
            ->where('verification_status', 'pending')
            ->count();
        $approvedPharmacies = User::where('role', User::ROLE_RETAILER)
            ->where('verification_status', 'approved')
            ->count();

        // Orders
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $deliveredOrders = Order::where('status', 'delivered')->count();

        return [
            'totalSales' => $totalSales,
            'monthlySales' => $monthlySales,
            'dailySales' => $dailySales,
            'totalCommission' => $totalCommission,
            'monthlyCommission' => $monthlyCommission,
            'totalSellerBalance' => $totalSellerBalance,
            'totalPendingBalance' => $totalPendingBalance,
            'pendingPayouts' => $pendingPayouts,
            'pendingPayoutCount' => $pendingPayoutCount,
            'completedPayouts' => $completedPayouts,
            'pendingPharmacies' => $pendingPharmacies,
            'approvedPharmacies' => $approvedPharmacies,
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'deliveredOrders' => $deliveredOrders,
        ];
    }

    public function getMonthlySalesChart(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $sales = Order::where('payment_status', 'paid')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('total_amount');
            $commission = Order::where('payment_status', 'paid')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('total_commission');

            $months->push([
                'month' => $date->translatedFormat('M Y'),
                'sales' => $sales,
                'commission' => $commission,
            ]);
        }
        return $months->toArray();
    }
}
