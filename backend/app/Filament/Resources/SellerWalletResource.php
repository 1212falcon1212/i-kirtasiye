<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SellerWalletResource\Pages;
use App\Models\SellerWallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class SellerWalletResource extends Resource
{
    protected static ?string $model = SellerWallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Satıcı Cüzdanları';

    protected static ?string $modelLabel = 'Cüzdan';

    protected static ?string $pluralModelLabel = 'Satıcı Cüzdanları';

    protected static ?string $navigationGroup = 'Finans';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cüzdan Bilgileri')
                    ->schema([
                        Forms\Components\Select::make('seller_id')
                            ->label('Satıcı')
                            ->relationship('seller', 'business_name')
                            ->disabled(),

                        Forms\Components\TextInput::make('balance')
                            ->label('Çekilebilir Bakiye')
                            ->suffix('₺')
                            ->disabled(),

                        Forms\Components\TextInput::make('pending_balance')
                            ->label('Bekleyen Bakiye')
                            ->suffix('₺')
                            ->disabled(),

                        Forms\Components\TextInput::make('withdrawn_balance')
                            ->label('Toplam Çekilen')
                            ->suffix('₺')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_earned')
                            ->label('Toplam Kazanç')
                            ->suffix('₺')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_commission')
                            ->label('Toplam Komisyon')
                            ->suffix('₺')
                            ->disabled(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seller.business_name')
                    ->label('Satıcı')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('seller.email')
                    ->label('E-posta')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Çekilebilir')
                    ->money('TRY')
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('pending_balance')
                    ->label('Bekleyen')
                    ->money('TRY')
                    ->sortable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('total_balance')
                    ->label('Toplam Bakiye')
                    ->money('TRY')
                    ->getStateUsing(fn(SellerWallet $record) => $record->balance + $record->pending_balance)
                    ->sortable(
                        query: fn($query, $direction) =>
                        $query->orderByRaw("(balance + pending_balance) {$direction}")
                    ),

                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Toplam Kazanç')
                    ->money('TRY')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_commission')
                    ->label('Kesilen Komisyon')
                    ->money('TRY')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('withdrawn_balance')
                    ->label('Çekilen')
                    ->money('TRY')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Son Güncelleme')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_balance')
                    ->label('Bakiyesi Olanlar')
                    ->query(fn($query) => $query->where('balance', '>', 0)),

                Tables\Filters\Filter::make('has_pending')
                    ->label('Bekleyen Bakiyesi Olanlar')
                    ->query(fn($query) => $query->where('pending_balance', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('balance', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Satıcı Bilgileri')
                    ->schema([
                        Infolists\Components\TextEntry::make('seller.business_name')
                            ->label('Firma Adı'),
                        Infolists\Components\TextEntry::make('seller.email')
                            ->label('E-posta'),
                        Infolists\Components\TextEntry::make('seller.city')
                            ->label('Şehir'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Bakiye Durumu')
                    ->schema([
                        Infolists\Components\TextEntry::make('balance')
                            ->label('Çekilebilir Bakiye')
                            ->money('TRY')
                            ->size('lg')
                            ->color('success'),
                        Infolists\Components\TextEntry::make('pending_balance')
                            ->label('Bekleyen Bakiye')
                            ->money('TRY')
                            ->size('lg')
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('total_earned')
                            ->label('Toplam Kazanç')
                            ->money('TRY')
                            ->size('lg'),
                        Infolists\Components\TextEntry::make('total_commission')
                            ->label('Kesilen Komisyon')
                            ->money('TRY')
                            ->size('lg')
                            ->color('danger'),
                        Infolists\Components\TextEntry::make('withdrawn_balance')
                            ->label('Toplam Çekilen')
                            ->money('TRY')
                            ->size('lg'),
                    ])
                    ->columns(5),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSellerWallets::route('/'),
            'view' => Pages\ViewSellerWallet::route('/{record}'),
        ];
    }
}
