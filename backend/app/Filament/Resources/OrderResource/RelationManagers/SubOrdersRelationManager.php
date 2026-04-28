<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\SubOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'subOrders';

    protected static ?string $title = 'Alt Siparişler';

    protected static ?string $modelLabel = 'Alt Sipariş';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('status')
                    ->label('Durum')
                    ->options(SubOrder::STATUS_LABELS)
                    ->required(),

                Forms\Components\TextInput::make('tracking_number')
                    ->label('Takip Numarası')
                    ->maxLength(100),

                Forms\Components\TextInput::make('shipping_provider')
                    ->label('Kargo Firması')
                    ->maxLength(50),

                Forms\Components\DateTimePicker::make('shipped_at')
                    ->label('Kargoya Verilme'),

                Forms\Components\DateTimePicker::make('delivered_at')
                    ->label('Teslim Tarihi'),

                Forms\Components\DateTimePicker::make('buyer_confirmed_at')
                    ->label('Alıcı Onay Tarihi'),

                Forms\Components\Textarea::make('notes')
                    ->label('Notlar')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seller.business_name')
                    ->label('Satıcı')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn($state) => SubOrder::STATUS_LABELS[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'processing' => 'primary',
                        'shipped' => 'info',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Tutar')
                    ->money('TRY')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_commission')
                    ->label('Komisyon')
                    ->money('TRY'),

                Tables\Columns\TextColumn::make('total_payout')
                    ->label('Hakediş')
                    ->money('TRY'),

                Tables\Columns\TextColumn::make('tracking_number')
                    ->label('Takip No')
                    ->copyable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('shipped_at')
                    ->label('Kargo Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('buyer_confirmed_at')
                    ->label('Alıcı Onayı')
                    ->dateTime('d.m.Y H:i')
                    ->color(fn($state) => $state ? 'success' : 'warning')
                    ->placeholder('Bekliyor'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }
}
