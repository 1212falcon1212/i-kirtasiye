<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportsPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.pages.financial-reports';

    protected static ?string $navigationLabel = 'Finansal Raporlar';

    protected static ?string $title = 'Finansal Raporlar';

    protected static ?string $navigationGroup = 'Finans';

    protected static ?int $navigationSort = 3;

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $reportType = 'daily';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        Forms\Components\DatePicker::make('startDate')
                            ->label('Başlangıç')
                            ->default(now()->startOfMonth())
                            ->required(),

                        Forms\Components\DatePicker::make('endDate')
                            ->label('Bitiş')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('reportType')
                            ->label('Rapor Tipi')
                            ->options([
                                'daily' => 'Günlük',
                                'weekly' => 'Haftalık',
                                'monthly' => 'Aylık',
                                'category' => 'Kategori Bazlı',
                                'seller' => 'Satıcı Bazlı',
                            ])
                            ->default('daily'),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('filter')
                                ->label('Filtrele')
                                ->action('applyFilter'),
                        ])->alignEnd(),
                    ]),
            ]);
    }

    public function applyFilter(): void
    {
        // Trigger table refresh
    }

    public function getSummaryStats(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $orders = Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid');

        $totalSales = (clone $orders)->sum('total_amount');
        $totalCommission = (clone $orders)->sum('total_commission');
        $orderCount = (clone $orders)->count();
        $avgOrderValue = $orderCount > 0 ? $totalSales / $orderCount : 0;

        // Invoices
        $invoices = Invoice::whereBetween('created_at', [$start, $end]);
        $invoiceCount = (clone $invoices)->count();
        $invoiceTotal = (clone $invoices)->sum('total_amount');

        // Previous period comparison
        $periodDays = $start->diffInDays($end) + 1;
        $prevStart = $start->copy()->subDays($periodDays);
        $prevEnd = $end->copy()->subDays($periodDays);

        $prevSales = Order::whereBetween('created_at', [$prevStart, $prevEnd])
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $salesChange = $prevSales > 0
            ? round((($totalSales - $prevSales) / $prevSales) * 100, 1)
            : ($totalSales > 0 ? 100 : 0);

        return [
            'totalSales' => $totalSales,
            'totalCommission' => $totalCommission,
            'orderCount' => $orderCount,
            'avgOrderValue' => $avgOrderValue,
            'invoiceCount' => $invoiceCount,
            'invoiceTotal' => $invoiceTotal,
            'salesChange' => $salesChange,
        ];
    }

    public function getDailyReport(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        return Order::whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as sales, SUM(total_commission) as commission, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('d.m.Y'),
                    'sales' => $item->sales,
                    'commission' => $item->commission,
                    'orders' => $item->orders,
                ];
            })
            ->toArray();
    }

    public function getCategoryReport(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        return OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('order_items.created_at', [$start, $end])
            ->where('orders.payment_status', 'paid')
            ->selectRaw('categories.name as category, SUM(order_items.total_price) as sales, SUM(order_items.commission_amount) as commission, COUNT(DISTINCT order_items.order_id) as orders')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('sales')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'sales' => $item->sales,
                    'commission' => $item->commission,
                    'orders' => $item->orders,
                ];
            })
            ->toArray();
    }

    public function getSellerReport(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        return OrderItem::join('users', 'order_items.seller_id', '=', 'users.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('order_items.created_at', [$start, $end])
            ->where('orders.payment_status', 'paid')
            ->selectRaw('users.business_name as seller, SUM(order_items.total_price) as sales, SUM(order_items.commission_amount) as commission, SUM(order_items.seller_payout_amount) as payout, COUNT(DISTINCT order_items.order_id) as orders')
            ->groupBy('users.id', 'users.business_name')
            ->orderByDesc('sales')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'seller' => $item->seller,
                    'sales' => $item->sales,
                    'commission' => $item->commission,
                    'payout' => $item->payout,
                    'orders' => $item->orders,
                ];
            })
            ->toArray();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->whereBetween('created_at', [
                        Carbon::parse($this->startDate)->startOfDay(),
                        Carbon::parse($this->endDate)->endOfDay(),
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Fatura No')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'seller' => 'Satış',
                        'commission' => 'Komisyon',
                        'tax' => 'Vergi',
                        default => $state,
                    })
                    ->color(fn($state) => match ($state) {
                        'seller' => 'success',
                        'commission' => 'info',
                        'tax' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('seller.business_name')
                    ->label('Satıcı')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Tutar')
                    ->money('TRY')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'draft' => 'Taslak',
                        'pending' => 'Beklemede',
                        'sent' => 'Gönderildi',
                        'paid' => 'Ödendi',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'seller' => 'Satış',
                        'commission' => 'Komisyon',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Taslak',
                        'pending' => 'Beklemede',
                        'sent' => 'Gönderildi',
                        'paid' => 'Ödendi',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
