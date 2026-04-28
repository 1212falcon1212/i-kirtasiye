<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutRequestResource\Pages;
use App\Models\PayoutRequest;
use App\Services\PayoutService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PayoutRequestResource extends Resource
{
    protected static ?string $model = PayoutRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Ödeme Talepleri';

    protected static ?string $modelLabel = 'Ödeme Talebi';

    protected static ?string $pluralModelLabel = 'Ödeme Talepleri';

    protected static ?string $navigationGroup = 'Finans';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) PayoutRequest::pending()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return PayoutRequest::pending()->count() > 0 ? 'warning' : 'gray';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Talep Bilgileri')
                    ->schema([
                        Forms\Components\Select::make('seller_id')
                            ->label('Satıcı')
                            ->relationship('seller', 'business_name')
                            ->disabled(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Tutar')
                            ->suffix('₺')
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options(PayoutRequest::$statusLabels)
                            ->disabled(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Banka Bilgileri')
                    ->schema([
                        Forms\Components\Placeholder::make('bank_name')
                            ->label('Banka')
                            ->content(fn(PayoutRequest $record): string => $record->bankAccount?->bank_name ?? '-'),

                        Forms\Components\Placeholder::make('iban')
                            ->label('IBAN')
                            ->content(fn(PayoutRequest $record): string => $record->bankAccount?->formatted_iban ?? '-'),

                        Forms\Components\Placeholder::make('account_holder')
                            ->label('Hesap Sahibi')
                            ->content(fn(PayoutRequest $record): string => $record->bankAccount?->account_holder ?? '-'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Notlar')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Satıcı Notu')
                            ->disabled()
                            ->rows(2),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notu')
                            ->rows(2),

                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('İşlem Referansı')
                            ->helperText('Banka dekont/referans numarası'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('seller.business_name')
                    ->label('Satıcı')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Tutar')
                    ->money('TRY')
                    ->sortable(),

                Tables\Columns\TextColumn::make('bankAccount.bank_name')
                    ->label('Banka')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => PayoutRequest::$statusLabels[$state] ?? $state)
                    ->color(fn(string $state): string => PayoutRequest::$statusColors[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Talep Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('İşlem Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(PayoutRequest::$statusLabels),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Ödeme Talebini Onayla')
                    ->modalDescription('Bu ödeme talebini onaylamak istediğinize emin misiniz?')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Not (isteğe bağlı)')
                            ->rows(2),
                    ])
                    ->action(function (PayoutRequest $record, array $data) {
                        $payoutService = app(PayoutService::class);
                        $payoutService->approveRequest($record, auth()->user(), $data['admin_notes'] ?? null);

                        Notification::make()
                            ->title('Talep onaylandı')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(PayoutRequest $record): bool => $record->canBeApproved()),

                Tables\Actions\Action::make('reject')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Ödeme Talebini Reddet')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Red nedeni')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function (PayoutRequest $record, array $data) {
                        $payoutService = app(PayoutService::class);
                        $payoutService->rejectRequest($record, auth()->user(), $data['admin_notes']);

                        Notification::make()
                            ->title('Talep reddedildi')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn(PayoutRequest $record): bool => $record->canBeRejected()),

                Tables\Actions\Action::make('complete')
                    ->label('Tamamla')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Ödemeyi Tamamla')
                    ->modalDescription('Transfer gerçekleştirildi mi?')
                    ->form([
                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transfer Referans No')
                            ->required(),
                    ])
                    ->action(function (PayoutRequest $record, array $data) {
                        $payoutService = app(PayoutService::class);
                        $payoutService->completeRequest($record, $data['transaction_reference']);

                        Notification::make()
                            ->title('Ödeme tamamlandı')
                            ->success()
                            ->send();
                    })
                    ->visible(fn(PayoutRequest $record): bool => $record->canBeCompleted()),

                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayoutRequests::route('/'),
            'view' => Pages\ViewPayoutRequest::route('/{record}'),
        ];
    }
}
