<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $chartData = $this->getMonthlySalesChart();
    @endphp

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Total Sales --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Satış</p>
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400">
                        {{ number_format($stats['totalSales'], 2, ',', '.') }} ₺
                    </p>
                </div>
                <div class="p-2 bg-success-50 dark:bg-success-950 rounded-lg">
                    <x-heroicon-o-banknotes class="w-6 h-6 text-success-600 dark:text-success-400" />
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Bu ay: {{ number_format($stats['monthlySales'], 2, ',', '.') }} ₺</p>
        </x-filament::section>

        {{-- Platform Commission --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Platform Komisyonu</p>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                        {{ number_format($stats['totalCommission'], 2, ',', '.') }} ₺
                    </p>
                </div>
                <div class="p-2 bg-primary-50 dark:bg-primary-950 rounded-lg">
                    <x-heroicon-o-chart-pie class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Bu ay: {{ number_format($stats['monthlyCommission'], 2, ',', '.') }} ₺
            </p>
        </x-filament::section>

        {{-- Seller Balances --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Satıcı Bakiyeleri</p>
                    <p class="text-2xl font-bold text-info-600 dark:text-info-400">
                        {{ number_format($stats['totalSellerBalance'], 2, ',', '.') }} ₺
                    </p>
                </div>
                <div class="p-2 bg-info-50 dark:bg-info-950 rounded-lg">
                    <x-heroicon-o-wallet class="w-6 h-6 text-info-600 dark:text-info-400" />
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">Bloke: {{ number_format($stats['totalPendingBalance'], 2, ',', '.') }}
                ₺</p>
        </x-filament::section>

        {{-- Pending Payouts --}}
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Bekleyen Ödemeler</p>
                    <p class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                        {{ number_format($stats['pendingPayouts'], 2, ',', '.') }} ₺
                    </p>
                </div>
                <div class="p-2 bg-warning-50 dark:bg-warning-950 rounded-lg">
                    <x-heroicon-o-clock class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ $stats['pendingPayoutCount'] }} adet talep</p>
        </x-filament::section>
    </div>

    {{-- Second Row --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Today Sales --}}
        <x-filament::section class="border-t-4 !border-t-success-500">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Bugünkü Satış</p>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                {{ number_format($stats['dailySales'], 2, ',', '.') }} ₺</p>
        </x-filament::section>

        {{-- Orders --}}
        <x-filament::section class="border-t-4 !border-t-info-500">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam Sipariş</p>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">{{ number_format($stats['totalOrders']) }}
            </p>
            <div class="flex gap-2 mt-1 text-xs text-gray-500 dark:text-gray-400">
                <span>Bekleyen: {{ $stats['pendingOrders'] }}</span>
                <span>•</span>
                <span>Teslim: {{ $stats['deliveredOrders'] }}</span>
            </div>
        </x-filament::section>

        {{-- Pharmacies --}}
        <x-filament::section class="border-t-4 !border-t-primary-500">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kırtasiyeler</p>
            <p class="text-3xl font-bold mt-2 text-gray-900 dark:text-white">
                {{ number_format($stats['approvedPharmacies']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Onay bekleyen: {{ $stats['pendingPharmacies'] }}
            </p>
        </x-filament::section>
    </div>

    {{-- Monthly Chart Table --}}
    <x-filament::section>
        <x-slot name="heading">Aylık Satış ve Komisyon</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Ay</th>
                        <th class="px-6 py-3 text-right">Satış</th>
                        <th class="px-6 py-3 text-right">Komisyon</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chartData as $data)
                        <tr
                            class="bg-white border-b dark:bg-gray-900 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $data['month'] }}</td>
                            <td class="px-6 py-4 text-right text-success-600 dark:text-success-400">
                                {{ number_format($data['sales'], 2, ',', '.') }} ₺
                            </td>
                            <td class="px-6 py-4 text-right text-primary-600 dark:text-primary-400">
                                {{ number_format($data['commission'], 2, ',', '.') }} ₺
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>