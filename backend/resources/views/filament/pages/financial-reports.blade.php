<x-filament-panels::page>
    @php
        $stats = $this->getSummaryStats();
        $dailyReport = $this->getDailyReport();
        $categoryReport = $this->getCategoryReport();
        $sellerReport = $this->getSellerReport();
    @endphp

    {{-- Date Filter --}}
    <x-filament::section class="mb-6">
        <form wire:submit="applyFilter">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Başlangıç</label>
                    <input type="date" wire:model.live="startDate"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm" />
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Bitiş</label>
                    <input type="date" wire:model.live="endDate"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm" />
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Rapor Tipi</label>
                    <select wire:model.live="reportType"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white shadow-sm">
                        <option value="daily">Günlük</option>
                        <option value="category">Kategori Bazlı</option>
                        <option value="seller">Satıcı Bazlı</option>
                    </select>
                </div>
                <div>
                    <x-filament::button type="submit" class="w-full">
                        Filtrele
                    </x-filament::button>
                </div>
            </div>
        </form>
    </x-filament::section>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
        <x-filament::section class="!p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Toplam Satış</p>
            <p class="text-xl font-bold text-success-600 dark:text-success-400 mt-1">
                {{ number_format($stats['totalSales'], 2, ',', '.') }} ₺
            </p>
            <p class="text-xs mt-1 {{ $stats['salesChange'] >= 0 ? 'text-success-500' : 'text-danger-500' }}">
                {{ $stats['salesChange'] >= 0 ? '+' : '' }}{{ $stats['salesChange'] }}% önceki döneme göre
            </p>
        </x-filament::section>

        <x-filament::section class="!p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Komisyon</p>
            <p class="text-xl font-bold text-primary-600 dark:text-primary-400 mt-1">
                {{ number_format($stats['totalCommission'], 2, ',', '.') }} ₺
            </p>
        </x-filament::section>

        <x-filament::section class="!p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sipariş Sayısı</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                {{ number_format($stats['orderCount']) }}
            </p>
        </x-filament::section>

        <x-filament::section class="!p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ort. Sipariş</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                {{ number_format($stats['avgOrderValue'], 2, ',', '.') }} ₺
            </p>
        </x-filament::section>

        <x-filament::section class="!p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fatura Sayısı</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">
                {{ number_format($stats['invoiceCount']) }}
            </p>
        </x-filament::section>

        <x-filament::section class="!p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fatura Toplamı</p>
            <p class="text-xl font-bold text-info-600 dark:text-info-400 mt-1">
                {{ number_format($stats['invoiceTotal'], 2, ',', '.') }} ₺
            </p>
        </x-filament::section>
    </div>

    {{-- Reports Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Daily Report --}}
        @if($reportType === 'daily')
            <x-filament::section class="col-span-full">
                <x-slot name="heading">Günlük Satış Raporu</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Tarih</th>
                                <th class="px-4 py-3 text-right">Satış</th>
                                <th class="px-4 py-3 text-right">Komisyon</th>
                                <th class="px-4 py-3 text-right">Sipariş</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dailyReport as $row)
                                <tr
                                    class="bg-white border-b dark:bg-gray-900 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['date'] }}</td>
                                    <td class="px-4 py-3 text-right text-success-600 dark:text-success-400">
                                        {{ number_format($row['sales'], 2, ',', '.') }} ₺</td>
                                    <td class="px-4 py-3 text-right text-primary-600 dark:text-primary-400">
                                        {{ number_format($row['commission'], 2, ',', '.') }} ₺</td>
                                    <td class="px-4 py-3 text-right">{{ $row['orders'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Bu dönemde veri bulunamadı.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Category Report --}}
        @if($reportType === 'category')
            <x-filament::section class="col-span-full">
                <x-slot name="heading">Kategori Bazlı Satış Raporu</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3 text-right">Satış</th>
                                <th class="px-4 py-3 text-right">Komisyon</th>
                                <th class="px-4 py-3 text-right">Sipariş</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categoryReport as $row)
                                <tr
                                    class="bg-white border-b dark:bg-gray-900 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['category'] }}</td>
                                    <td class="px-4 py-3 text-right text-success-600 dark:text-success-400">
                                        {{ number_format($row['sales'], 2, ',', '.') }} ₺</td>
                                    <td class="px-4 py-3 text-right text-primary-600 dark:text-primary-400">
                                        {{ number_format($row['commission'], 2, ',', '.') }} ₺</td>
                                    <td class="px-4 py-3 text-right">{{ $row['orders'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Bu dönemde veri bulunamadı.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- Seller Report --}}
        @if($reportType === 'seller')
            <x-filament::section class="col-span-full">
                <x-slot name="heading">Satıcı Bazlı Satış Raporu (Top 20)</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Satıcı</th>
                                <th class="px-4 py-3 text-right">Satış</th>
                                <th class="px-4 py-3 text-right">Komisyon</th>
                                <th class="px-4 py-3 text-right">Hakediş</th>
                                <th class="px-4 py-3 text-right">Sipariş</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sellerReport as $row)
                                <tr
                                    class="bg-white border-b dark:bg-gray-900 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['seller'] }}</td>
                                    <td class="px-4 py-3 text-right text-success-600 dark:text-success-400">
                                        {{ number_format($row['sales'], 2, ',', '.') }} ₺</td>
                                    <td class="px-4 py-3 text-right text-primary-600 dark:text-primary-400">
                                        {{ number_format($row['commission'], 2, ',', '.') }} ₺</td>
                                    <td class="px-4 py-3 text-right text-info-600 dark:text-info-400">
                                        {{ number_format($row['payout'], 2, ',', '.') }} ₺</td>
                                    <td class="px-4 py-3 text-right">{{ $row['orders'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Bu dönemde veri bulunamadı.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>

    {{-- Invoice Table --}}
    <x-filament::section>
        <x-slot name="heading">Faturalar</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>