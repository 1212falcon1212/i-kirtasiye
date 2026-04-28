<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Siparişler';

    protected static ?string $modelLabel = 'Sipariş';

    protected static ?string $pluralModelLabel = 'Siparişler';

    protected static ?string $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sipariş Bilgileri')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Sipariş No')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Sipariş Durumu')
                            ->options(Order::STATUS_LABELS)
                            ->required(),

                        Forms\Components\Select::make('payment_status')
                            ->label('Ödeme Durumu')
                            ->options(Order::PAYMENT_STATUS_LABELS)
                            ->required(),

                        Forms\Components\Select::make('shipping_status')
                            ->label('Kargo Durumu')
                            ->options(Order::SHIPPING_STATUS_LABELS),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tutar Bilgileri')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal')
                            ->label('Ara Toplam')
                            ->disabled()
                            ->suffix('₺'),

                        Forms\Components\TextInput::make('total_commission')
                            ->label('Komisyon')
                            ->disabled()
                            ->suffix('₺'),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Toplam')
                            ->disabled()
                            ->suffix('₺'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Kargo Bilgileri')
                    ->schema([
                        Forms\Components\TextInput::make('shipping_provider')
                            ->label('Kargo Firması'),

                        Forms\Components\TextInput::make('tracking_number')
                            ->label('Takip Numarası'),

                        Forms\Components\DateTimePicker::make('shipped_at')
                            ->label('Kargoya Verilme'),

                        Forms\Components\DateTimePicker::make('delivered_at')
                            ->label('Teslim Tarihi'),

                        Forms\Components\DateTimePicker::make('buyer_confirmed_at')
                            ->label('Alıcı Onay Tarihi')
                            ->helperText('Alıcının teslimatı onayladığı tarih'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notlar')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Sipariş Notları')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Sipariş No')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.business_name')
                    ->label('Alıcı')
                    ->searchable()
                    ->sortable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Ürün')
                    ->counts('items')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Toplam')
                    ->money('TRY')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('TRY')),

                Tables\Columns\TextColumn::make('total_commission')
                    ->label('Komisyon')
                    ->money('TRY')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Order::STATUS_LABELS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'processing' => 'primary',
                        'shipped' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Ödeme')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Order::PAYMENT_STATUS_LABELS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('shipping_status')
                    ->label('Kargo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Order::SHIPPING_STATUS_LABELS[$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('Takip No')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('buyer_confirmed_at')
                    ->label('Alıcı Onayı')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->placeholder('Bekliyor')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Sipariş Durumu')
                    ->options(Order::STATUS_LABELS),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Ödeme Durumu')
                    ->options(Order::PAYMENT_STATUS_LABELS),

                Tables\Filters\SelectFilter::make('shipping_status')
                    ->label('Kargo Durumu')
                    ->options(Order::SHIPPING_STATUS_LABELS),

                Tables\Filters\Filter::make('buyer_confirmation_pending')
                    ->label('Alıcı Onayı Bekleyen')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'delivered')->whereNull('buyer_confirmed_at'))
                    ->toggle(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Başlangıç'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Bitiş'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approved')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record) => $record->status === 'pending')
                    ->action(fn (Order $record) => $record->update(['status' => 'confirmed'])),
                Tables\Actions\Action::make('cancel')
                    ->label('İptal Et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record) => $record->canBeCancelled())
                    ->requiresConfirmation()
                    ->action(fn (Order $record) => $record->cancel()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsConfirmed')
                        ->label('Toplu Onayla')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'confirmed']))),
                    Tables\Actions\BulkAction::make('markAsShipped')
                        ->label('Kargoya Verildi')
                        ->icon('heroicon-o-truck')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'shipped', 'shipping_status' => 'shipped', 'shipped_at' => now()]))),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Sipariş Bilgileri')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('order_number')
                                    ->label('Sipariş No')
                                    ->weight('bold')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('status')
                                    ->label('Durum')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => Order::STATUS_LABELS[$state] ?? $state)
                                    ->color(fn ($state) => match ($state) {
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'shipped' => 'info',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('payment_status')
                                    ->label('Ödeme')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => Order::PAYMENT_STATUS_LABELS[$state] ?? $state)
                                    ->color(fn ($state) => match ($state) {
                                        'paid' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'gray',
                                    }),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Sipariş Tarihi')
                                    ->dateTime('d.m.Y H:i'),

                                Infolists\Components\TextEntry::make('shipped_at')
                                    ->label('Kargo Tarihi')
                                    ->dateTime('d.m.Y H:i'),

                                Infolists\Components\TextEntry::make('delivered_at')
                                    ->label('Teslim Tarihi')
                                    ->dateTime('d.m.Y H:i'),

                                Infolists\Components\TextEntry::make('buyer_confirmed_at')
                                    ->label('Alıcı Onay Tarihi')
                                    ->dateTime('d.m.Y H:i')
                                    ->color(fn ($state) => $state ? 'success' : 'warning')
                                    ->placeholder('Henüz onaylanmadı'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Alıcı Bilgileri')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('user.business_name')
                                    ->label('Firma Adı'),

                                Infolists\Components\TextEntry::make('user.email')
                                    ->label('E-posta')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('user.phone')
                                    ->label('Telefon')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('shipping_address.city')
                                    ->label('İl'),

                                Infolists\Components\TextEntry::make('shipping_address.district')
                                    ->label('İlçe'),

                                Infolists\Components\TextEntry::make('shipping_address.address')
                                    ->label('Adres')
                                    ->columnSpan(3),
                            ]),
                    ]),

                Infolists\Components\Section::make('Tutar Detayları')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('subtotal')
                                    ->label('Ara Toplam')
                                    ->money('TRY'),

                                Infolists\Components\TextEntry::make('total_commission')
                                    ->label('Platform Komisyonu')
                                    ->money('TRY')
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('shipping_cost')
                                    ->label('Kargo')
                                    ->money('TRY'),

                                Infolists\Components\TextEntry::make('total_amount')
                                    ->label('Toplam')
                                    ->money('TRY')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Kargo Bilgileri')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('shipping_provider')
                                    ->label('Kargo Firması'),

                                Infolists\Components\TextEntry::make('tracking_number')
                                    ->label('Takip Numarası')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('shipping_status')
                                    ->label('Kargo Durumu')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => Order::SHIPPING_STATUS_LABELS[$state] ?? $state),

                                Infolists\Components\TextEntry::make('shipping_label_url')
                                    ->label('Kargo Etiketi')
                                    ->url(fn ($state) => $state, shouldOpenInNewTab: true)
                                    ->visible(fn ($state) => ! empty($state)),
                            ]),
                    ]),

                Infolists\Components\Section::make('Aras Kargo Detayları')
                    ->visible(fn ($record) => $record->shipping_provider === 'aras')
                    ->collapsible()
                    ->schema([
                        Infolists\Components\TextEntry::make('aras_details')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                try {
                                    $provider = app(\App\Services\Shipping\ArasProvider::class);
                                    if (! $provider->isAvailable()) {
                                        return 'Aras Kargo entegrasyonu aktif değil.';
                                    }

                                    $detail = $provider->getDetailedInfo($record);
                                    if (empty($detail)) {
                                        return 'Detay bilgisi alınamadı. Kargo henüz şubede işleme alınmamış olabilir.';
                                    }

                                    $rows = [
                                        ['Kargo Takip No', $detail['tracking_number'] ?? '-'],
                                        ['İrsaliye No', $detail['irsaliye_numara'] ?? '-'],
                                        ['Çıkış Şubesi', $detail['cikis_sube'] ?? '-'],
                                        ['Varış Şubesi', $detail['varis_sube'] ?? '-'],
                                        ['Parça Adedi', $detail['adet'] ?? '-'],
                                        ['Desi', $detail['desi'] ?? '-'],
                                        ['Durumu', $detail['durumu'] ?? '-'],
                                        ['Teslim Alan', $detail['teslim_alan'] ?? '-'],
                                        ['Teslim Tarihi', $detail['teslim_tarihi'] ?? '-'],
                                    ];

                                    if (! empty($detail['iade_sebebi'])) {
                                        $rows[] = ['İade Sebebi', $detail['iade_sebebi']];
                                    }
                                    if (! empty($detail['devir_kodu'])) {
                                        $rows[] = ['Devir Kodu', \App\Services\Shipping\ArasProvider::getDevirReason($detail['devir_kodu'])];
                                    }

                                    $html = '<table style="width:100%;font-size:13px"><tbody>';
                                    foreach ($rows as [$label, $value]) {
                                        $html .= '<tr><td style="padding:4px 8px;color:#6b7280;width:180px">'.e($label).'</td>';
                                        $html .= '<td style="padding:4px 8px;font-weight:500">'.e($value).'</td></tr>';
                                    }
                                    $html .= '</tbody></table>';

                                    $history = $provider->getTrackingHistory($record);
                                    if (! empty($history)) {
                                        $html .= '<h4 style="margin-top:16px;font-weight:bold">Hareket Geçmişi</h4>';
                                        $html .= '<ul style="margin-top:8px;font-size:12px;list-style:none;padding-left:12px;border-left:2px solid #e5e7eb">';
                                        foreach ($history as $event) {
                                            $html .= '<li style="margin-bottom:8px;padding-left:12px;position:relative">';
                                            $html .= '<strong>'.e($event['date']).'</strong> — '.e($event['action']);
                                            if (! empty($event['location'])) {
                                                $html .= ' <span style="color:#6b7280">('.e($event['location']).')</span>';
                                            }
                                            if (! empty($event['description'])) {
                                                $html .= '<br><span style="color:#6b7280">'.e($event['description']).'</span>';
                                            }
                                            $html .= '</li>';
                                        }
                                        $html .= '</ul>';
                                    }

                                    return $html;
                                } catch (\Throwable $e) {
                                    return 'Hata: '.$e->getMessage();
                                }
                            })
                            ->html(),
                    ]),

                Infolists\Components\Section::make('Sipariş Kalemleri')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label('Ürün'),

                                Infolists\Components\TextEntry::make('seller.business_name')
                                    ->label('Satıcı'),

                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Adet'),

                                Infolists\Components\TextEntry::make('unit_price')
                                    ->label('Birim Fiyat')
                                    ->money('TRY'),

                                Infolists\Components\TextEntry::make('total_price')
                                    ->label('Toplam')
                                    ->money('TRY'),

                                Infolists\Components\TextEntry::make('commission_amount')
                                    ->label('Komisyon')
                                    ->money('TRY'),

                                Infolists\Components\TextEntry::make('seller_payout_amount')
                                    ->label('Hakediş')
                                    ->money('TRY'),
                            ])
                            ->columns(7),
                    ]),

                Infolists\Components\Section::make('Notlar')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('')
                            ->default('Not yok'),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrderResource\RelationManagers\SubOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
