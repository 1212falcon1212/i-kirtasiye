<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemLogResource\Pages;
use App\Models\SystemLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SystemLogResource extends Resource
{
    protected static ?string $model = SystemLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Sistem Logları';

    protected static ?string $modelLabel = 'Sistem Logu';

    protected static ?string $pluralModelLabel = 'Sistem Logları';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Log Detayları')
                    ->schema([
                        Forms\Components\TextInput::make('type')
                            ->label('Tip')
                            ->disabled(),
                        Forms\Components\TextInput::make('provider')
                            ->label('Sağlayıcı')
                            ->disabled(),
                        Forms\Components\TextInput::make('action')
                            ->label('Aksiyon')
                            ->disabled(),
                        Forms\Components\TextInput::make('response_code')
                            ->label('HTTP Kodu')
                            ->disabled(),
                        Forms\Components\TextInput::make('status')
                            ->label('Durum')
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('İstek')
                    ->schema([
                        Forms\Components\Textarea::make('request')
                            ->label('Request Body')
                            ->disabled()
                            ->rows(10)
                            ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state),
                    ]),

                Forms\Components\Section::make('Yanıt')
                    ->schema([
                        Forms\Components\Textarea::make('response')
                            ->label('Response Body')
                            ->disabled()
                            ->rows(10)
                            ->formatStateUsing(fn($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state),
                        Forms\Components\Textarea::make('error')
                            ->label('Hata Mesajı')
                            ->disabled()
                            ->rows(3)
                            ->visible(fn($record) => !empty($record?->error)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tip')
                    ->colors([
                        'primary' => 'invoice',
                        'info' => 'shipping',
                        'success' => 'payment',
                        'warning' => 'auth',
                    ])
                    ->formatStateUsing(fn($state) => SystemLog::TYPE_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Sağlayıcı')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('action')
                    ->label('Aksiyon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Sipariş')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('response_code')
                    ->label('HTTP')
                    ->badge()
                    ->color(fn($state) => $state >= 200 && $state < 300 ? 'success' : ($state >= 400 ? 'danger' : 'warning')),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'success' => 'success',
                        'danger' => 'failed',
                        'warning' => 'pending',
                    ])
                    ->formatStateUsing(fn($state) => SystemLog::STATUS_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('error')
                    ->label('Hata')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->error)
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tip')
                    ->options(SystemLog::TYPE_LABELS),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(SystemLog::STATUS_LABELS),
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Sağlayıcı')
                    ->options([
                        'bizimhesap' => 'Bizimhesap',
                        'aras' => 'Aras Kargo',
                        'yurtici' => 'Yurtiçi Kargo',
                        'mng' => 'MNG Kargo',
                        'sendeo' => 'Sendeo',
                        'hepsijet' => 'Hepsijet',
                        'ptt' => 'PTT Kargo',
                    ]),
                Tables\Filters\Filter::make('failed_only')
                    ->label('Sadece Hatalar')
                    ->query(fn($query) => $query->where('status', 'failed')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemLogs::route('/'),
            'view' => Pages\ViewSystemLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
