<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Kuponlar';

    protected static ?string $modelLabel = 'Kupon';

    protected static ?string $pluralModelLabel = 'Kuponlar';

    protected static ?string $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kupon Bilgileri')
                    ->schema([
                        Forms\Components\Select::make('seller_id')
                            ->label('Satıcı')
                            ->relationship('seller', 'business_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('code')
                            ->label('Kupon Kodu')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('name')
                            ->label('Kupon Adı')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(2)
                            ->maxLength(1000),
                    ])->columns(2),

                Forms\Components\Section::make('İndirim Detayları')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->label('İndirim Tipi')
                            ->options(Coupon::DISCOUNT_TYPE_LABELS)
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('discount_value')
                            ->label('İndirim Değeri')
                            ->numeric()
                            ->required(fn (Forms\Get $get): bool => $get('discount_type') !== Coupon::DISCOUNT_TYPE_FREE_SHIPPING)
                            ->minValue(0.01)
                            ->hidden(fn (Forms\Get $get): bool => $get('discount_type') === Coupon::DISCOUNT_TYPE_FREE_SHIPPING),
                        Forms\Components\TextInput::make('min_purchase_amount')
                            ->label('Min. Sepet Tutarı')
                            ->numeric()
                            ->prefix('₺'),
                        Forms\Components\TextInput::make('max_discount_amount')
                            ->label('Maks. İndirim Tutarı')
                            ->numeric()
                            ->prefix('₺')
                            ->helperText('Yüzde indirimler için üst limit')
                            ->hidden(fn (Forms\Get $get): bool => $get('discount_type') === Coupon::DISCOUNT_TYPE_FREE_SHIPPING),
                    ])->columns(2),

                Forms\Components\Section::make('Kullanım Limitleri')
                    ->schema([
                        Forms\Components\TextInput::make('usage_limit')
                            ->label('Toplam Kullanım Limiti')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Boş bırakılırsa sınırsız'),
                        Forms\Components\TextInput::make('usage_limit_per_user')
                            ->label('Kişi Başı Limit')
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                        Forms\Components\TextInput::make('used_count')
                            ->label('Kullanım Sayısı')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ])->columns(3),

                Forms\Components\Section::make('Tarih ve Durum')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Başlangıç')
                            ->native(false),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Bitiş')
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options(Coupon::STATUS_LABELS)
                            ->required()
                            ->default('active'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kod')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Kupon kodu kopyalandı')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Ad')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('seller.business_name')
                    ->label('Satıcı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('discount_type')
                    ->label('Tip')
                    ->colors([
                        'success' => 'percentage',
                        'info' => 'fixed',
                        'warning' => 'free_shipping',
                    ])
                    ->formatStateUsing(fn(string $state): string => Coupon::DISCOUNT_TYPE_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('discount_value')
                    ->label('Değer')
                    ->formatStateUsing(function ($record) {
                        if ($record->discount_type === 'free_shipping') {
                            return 'Ücretsiz Kargo';
                        }
                        if ($record->discount_type === 'percentage') {
                            return '%' . number_format($record->discount_value, 0);
                        }
                        return '₺' . number_format($record->discount_value, 2);
                    }),
                Tables\Columns\TextColumn::make('used_count')
                    ->label('Kullanım')
                    ->formatStateUsing(function ($record) {
                        if ($record->usage_limit) {
                            return "{$record->used_count}/{$record->usage_limit}";
                        }
                        return $record->used_count;
                    })
                    ->badge()
                    ->color(fn($record) => $record->usage_limit && $record->used_count >= $record->usage_limit ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Bitiş')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->color(fn($record) => $record->ends_at && $record->ends_at->isPast() ? 'danger' : 'success'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'success' => 'active',
                        'gray' => 'inactive',
                        'danger' => 'expired',
                    ])
                    ->formatStateUsing(fn(string $state): string => Coupon::STATUS_LABELS[$state] ?? $state),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(Coupon::STATUS_LABELS),
                Tables\Filters\SelectFilter::make('discount_type')
                    ->label('İndirim Tipi')
                    ->options(Coupon::DISCOUNT_TYPE_LABELS),
                Tables\Filters\SelectFilter::make('seller_id')
                    ->label('Satıcı')
                    ->relationship('seller', 'business_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
