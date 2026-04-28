<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Kampanya Onayları';

    protected static ?string $modelLabel = 'Kampanya';

    protected static ?string $pluralModelLabel = 'Kampanyalar';

    protected static ?string $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kampanya Bilgileri')
                    ->schema([
                        Forms\Components\Select::make('seller_id')
                            ->label('Satıcı')
                            ->relationship('seller', 'business_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('name')
                            ->label('Kampanya Adı')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\Select::make('type')
                            ->label('Kampanya Tipi')
                            ->options(Campaign::TYPE_LABELS)
                            ->required()
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('İndirim Detayları')
                    ->schema([
                        Forms\Components\TextInput::make('discount_rate')
                            ->label('İndirim Oranı (%)')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(1)
                            ->maxValue(100),
                        Forms\Components\TextInput::make('min_purchase_amount')
                            ->label('Min. Sepet Tutarı')
                            ->numeric()
                            ->prefix('₺'),
                        Forms\Components\TextInput::make('min_quantity')
                            ->label('Min. Adet')
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\Select::make('product_id')
                            ->label('Ürün')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn($record) => $record?->type === 'product_discount'),
                        Forms\Components\TextInput::make('brand')
                            ->label('Marka')
                            ->visible(fn($record) => $record?->type === 'brand_discount'),
                        Forms\Components\Select::make('gift_product_id')
                            ->label('Hediye Ürün')
                            ->relationship('giftProduct', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn($record) => $record?->type === 'gift_product'),
                        Forms\Components\TextInput::make('gift_quantity')
                            ->label('Hediye Adet')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn($record) => $record?->type === 'gift_product'),
                    ])->columns(2),

                Forms\Components\Section::make('Tarih Aralığı')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Başlangıç')
                            ->required()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Bitiş')
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options(Campaign::STATUS_LABELS)
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Onay Bilgileri')
                    ->schema([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Ret Sebebi')
                            ->rows(3)
                            ->visible(fn($record) => $record?->status === 'rejected'),
                    ])
                    ->visible(fn($record) => $record?->status === 'rejected'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kampanya Adı')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('seller.business_name')
                    ->label('Satıcı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tip')
                    ->colors([
                        'success' => 'product_discount',
                        'info' => 'store_discount',
                        'warning' => 'brand_discount',
                        'primary' => 'gift_product',
                    ])
                    ->formatStateUsing(fn(string $state): string => Campaign::TYPE_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('discount_rate')
                    ->label('İndirim')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Başlangıç')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Bitiş')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'gray' => 'inactive',
                        'danger' => fn($state) => in_array($state, ['rejected', 'expired']),
                    ])
                    ->formatStateUsing(fn(string $state): string => Campaign::STATUS_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(Campaign::STATUS_LABELS)
                    ->default('pending'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tip')
                    ->options(Campaign::TYPE_LABELS),
                Tables\Filters\SelectFilter::make('seller_id')
                    ->label('Satıcı')
                    ->relationship('seller', 'business_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Kampanyayı Onayla')
                    ->modalDescription('Bu kampanyayı onaylamak istediğinize emin misiniz?')
                    ->action(function ($record) {
                        $record->update([
                            'status' => Campaign::STATUS_ACTIVE,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'rejection_reason' => null,
                        ]);

                        Notification::make()
                            ->title('Kampanya onaylandı')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Ret Sebebi')
                            ->required()
                            ->rows(3)
                            ->placeholder('Lütfen ret sebebini belirtiniz...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => Campaign::STATUS_REJECTED,
                            'rejection_reason' => $data['rejection_reason'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Kampanya reddedildi')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkApprove')
                        ->label('Toplu Onayla')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => Campaign::STATUS_ACTIVE,
                                        'reviewed_by' => auth()->id(),
                                        'reviewed_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} kampanya onaylandı")
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
