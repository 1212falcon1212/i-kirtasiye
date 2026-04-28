<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Services\Invoicing\BizimhesapService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class InvoicesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.invoices';

    protected static ?string $navigationLabel = 'Faturalar';

    protected static ?string $title = 'Faturalar';

    protected static ?string $navigationGroup = 'Finans';

    protected static ?int $navigationSort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query()->with(['order', 'seller', 'subOrder']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Fatura No')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Siparis No')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('seller.business_name')
                    ->label('Satici')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tip')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'seller' => 'Satis',
                        'commission' => 'Komisyon',
                        'tax' => 'Vergi',
                        'shipping' => 'Kargo',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'seller' => 'info',
                        'commission' => 'warning',
                        'tax' => 'gray',
                        'shipping' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Toplam')
                    ->money('TRY')
                    ->sortable(),

                Tables\Columns\TextColumn::make('erp_status')
                    ->label('Fatura Durumu')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'synced' => 'Fatura Olusturuldu',
                        'failed' => 'Basarisiz',
                        'pending' => 'Fatura Kesilmedi',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'synced' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tip')
                    ->options([
                        'seller' => 'Satis Faturasi',
                        'commission' => 'Komisyon Faturasi',
                        'tax' => 'Vergi Faturasi',
                        'shipping' => 'Kargo Faturasi',
                    ]),

                Tables\Filters\SelectFilter::make('erp_status')
                    ->label('Fatura Durumu')
                    ->options([
                        'pending' => 'Fatura Kesilmedi',
                        'synced' => 'Fatura Olusturuldu',
                        'failed' => 'Basarisiz',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Baslangic'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Bitis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn(Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn(Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('create_invoice')
                    ->label('Fatura Kes')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->visible(fn(Invoice $record): bool => $record->erp_status === 'pending' || $record->erp_status === 'failed')
                    ->requiresConfirmation()
                    ->modalHeading('Fatura Kes')
                    ->modalDescription('Bu fatura BizimHesap uzerinden kesilecek. Devam etmek istiyor musunuz?')
                    ->modalSubmitActionLabel('Fatura Kes')
                    ->action(function (Invoice $record): void {
                        $this->createErpInvoice($record);
                    }),

                Tables\Actions\Action::make('view_detail')
                    ->label('Detay')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn(Invoice $record): string => 'Fatura #' . $record->invoice_number)
                    ->modalContent(fn(Invoice $record) => view('filament.pages.invoice-detail', ['invoice' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Kapat'),

                Tables\Actions\Action::make('view_erp')
                    ->label('BizimHesap')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn(Invoice $record): ?string => $record->erp_invoice_url)
                    ->openUrlInNewTab()
                    ->visible(fn(Invoice $record): bool => !empty($record->erp_invoice_url)),

                Tables\Actions\Action::make('delete_invoice')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Fatura Sil')
                    ->modalDescription('Bu fatura kalici olarak silinecek. Bu islem geri alinamaz.')
                    ->modalSubmitActionLabel('Evet, Sil')
                    ->action(function (Invoice $record): void {
                        $record->delete();
                        Notification::make()->title('Fatura Silindi')->body('Fatura basariyla silindi.')->success()->send();
                    }),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Fatura Bulunamadi')
            ->emptyStateDescription('Henuz olusturulmus fatura bulunmuyor.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    /**
     * Create invoice on BizimHesap via API using stored Invoice items
     */
    protected function createErpInvoice(Invoice $record): void
    {
        // Ensure order relation is loaded
        $record->loadMissing(['order.user']);

        if (!$record->order) {
            Notification::make()->title('Hata')->body('Siparis bilgisi bulunamadi, fatura kesilemez.')->danger()->send();
            return;
        }

        try {
            $bizimhesap = new BizimhesapService();
            $result = $bizimhesap->createInvoiceFromRecord($record);

            if ($result->success) {
                $record->markAsSynced($result->invoiceId, $result->invoiceUrl);
                Notification::make()
                    ->title('Fatura Olusturuldu')
                    ->body('Fatura BizimHesap uzerinde basariyla olusturuldu.')
                    ->success()
                    ->send();
            } else {
                $record->markAsFailed($result->error ?? $result->message ?? 'Bilinmeyen hata');
                Notification::make()
                    ->title('Fatura Kesilemedi')
                    ->body($result->error ?? 'BizimHesap API hatasi')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('ERP invoice creation failed for #' . $record->invoice_number . ': ' . $e->getMessage());
            $record->markAsFailed('Exception: ' . $e->getMessage());
            Notification::make()->title('Hata')->body('Beklenmedik hata: ' . $e->getMessage())->danger()->send();
        }
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = Invoice::where('erp_status', 'pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $failedCount = Invoice::where('erp_status', 'failed')->count();
        return $failedCount > 0 ? 'danger' : 'warning';
    }
}
