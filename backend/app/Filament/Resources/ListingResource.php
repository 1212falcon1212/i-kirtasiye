<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ListingResource\Pages;
use App\Models\Offer;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ListingResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'İlanlar';

    protected static ?string $modelLabel = 'İlan';

    protected static ?string $pluralModelLabel = 'İlanlar';

    protected static ?string $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'listings';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'active')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Sol (2/3)
                        Forms\Components\Section::make('İlan Bilgileri')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Ürün')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('seller_id')
                                    ->label('Satıcı')
                                    ->relationship('seller', 'business_name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('price')
                                    ->label('Fiyat')
                                    ->numeric()
                                    ->required()
                                    ->prefix('₺')
                                    ->step(0.01),
                                Forms\Components\TextInput::make('stock')
                                    ->label('Stok')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0),
                                Forms\Components\DatePicker::make('expiry_date')
                                    ->label('Son Kullanma Tarihi')
                                    ->native(false),
                                Forms\Components\TextInput::make('batch_number')
                                    ->label('Parti No')
                                    ->maxLength(50),
                            ])->columns(2)
                            ->columnSpan(2),

                        // Sag (1/3)
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Section::make('Durum')
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->label('Durum')
                                            ->options(Offer::STATUS_LABELS)
                                            ->required(),
                                    ]),
                                Forms\Components\Section::make('Notlar')
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Satıcı Notları')
                                            ->rows(3)
                                            ->maxLength(500),
                                        Forms\Components\Textarea::make('rejection_reason')
                                            ->label('Ret Sebebi')
                                            ->rows(2)
                                            ->visible(fn ($record) => $record?->status === 'rejected'),
                                    ]),
                            ])->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('product_image')
                    ->label('Görsel')
                    ->height(50)
                    ->width(50)
                    ->circular(false)
                    ->getStateUsing(fn (Offer $record): ?string => $record->product?->image_url)
                    ->defaultImageUrl('https://placehold.co/50x50/f1f5f9/94a3b8?text=—'),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Ürün')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->description(fn (Offer $record): string => $record->product?->barcode ?? ''),
                Tables\Columns\TextColumn::make('seller.business_name')
                    ->label('Satıcı')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Offer $record): string => match ($record->seller?->role) {
                        'retailer' => 'Kırtasiyeci',
                        'seller' => 'Tedarikçi',
                        default => '',
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->label('Fiyat')
                    ->money('TRY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('SKT')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('Yok')
                    ->color(fn ($record): ?string => $record->expiry_date?->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'active' => 'success',
                        'inactive' => 'gray',
                        'sold_out' => 'danger',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Offer::STATUS_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('product.activeOffers')
                    ->label('Toplam İlan')
                    ->getStateUsing(fn (Offer $record): int => $record->product?->activeOffers?->count() ?? 0)
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(Offer::STATUS_LABELS)
                    ->multiple(),
                Tables\Filters\SelectFilter::make('seller_id')
                    ->label('Satıcı')
                    ->relationship('seller', 'business_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('expired')
                    ->label('SKT Geçmiş')
                    ->query(fn ($query) => $query->whereNotNull('expiry_date')->where('expiry_date', '<', now()))
                    ->toggle(),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Stok Tükenmiş')
                    ->query(fn ($query) => $query->where('stock', '<=', 0))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detay'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Aktifleştir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Offer $record) => in_array($record->status, ['pending', 'inactive', 'rejected']))
                    ->requiresConfirmation()
                    ->action(function (Offer $record) {
                        $record->update([
                            'status' => Offer::STATUS_ACTIVE,
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'rejection_reason' => null,
                        ]);
                        Notification::make()->title('İlan aktifleştirildi')->success()->send();
                    }),
                Tables\Actions\Action::make('deactivate')
                    ->label('Pasifleştir')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->visible(fn (Offer $record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(function (Offer $record) {
                        $record->update(['status' => Offer::STATUS_INACTIVE]);
                        Notification::make()->title('İlan pasifleştirildi')->warning()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkActivate')
                        ->label('Toplu Aktifleştir')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status !== 'active') {
                                    $record->update([
                                        'status' => Offer::STATUS_ACTIVE,
                                        'reviewed_by' => auth()->id(),
                                        'reviewed_at' => now(),
                                    ]);
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} ilan aktifleştirildi")->success()->send();
                        }),
                    Tables\Actions\BulkAction::make('bulkDeactivate')
                        ->label('Toplu Pasifleştir')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'active') {
                                    $record->update(['status' => Offer::STATUS_INACTIVE]);
                                    $count++;
                                }
                            }
                            Notification::make()->title("{$count} ilan pasifleştirildi")->warning()->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(3)
                    ->schema([
                        // Sol (2/3) - Ürün + İlan bilgileri
                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\Section::make('Ürün Bilgileri')
                                    ->schema([
                                        Infolists\Components\ImageEntry::make('product_image')
                                            ->label('Görsel')
                                            ->height(120)
                                            ->getStateUsing(fn ($record): ?string => $record->product?->image_url)
                                            ->columnSpanFull(),
                                        Infolists\Components\TextEntry::make('product.name')
                                            ->label('Ürün Adı'),
                                        Infolists\Components\TextEntry::make('product.barcode')
                                            ->label('Barkod')
                                            ->copyable(),
                                        Infolists\Components\TextEntry::make('product.brand')
                                            ->label('Marka'),
                                        Infolists\Components\TextEntry::make('product.category.name')
                                            ->label('Kategori'),
                                    ])->columns(2),

                                Infolists\Components\Section::make('İlan Detayları')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('price')
                                            ->label('Fiyat')
                                            ->money('TRY'),
                                        Infolists\Components\TextEntry::make('stock')
                                            ->label('Stok Adedi')
                                            ->badge()
                                            ->color(fn (int $state): string => match (true) {
                                                $state <= 0 => 'danger',
                                                $state <= 10 => 'warning',
                                                default => 'success',
                                            }),
                                        Infolists\Components\TextEntry::make('expiry_date')
                                            ->label('Son Kullanma Tarihi')
                                            ->date('d.m.Y')
                                            ->placeholder('Belirtilmemiş'),
                                        Infolists\Components\TextEntry::make('batch_number')
                                            ->label('Parti No')
                                            ->placeholder('—'),
                                        Infolists\Components\TextEntry::make('notes')
                                            ->label('Satıcı Notları')
                                            ->placeholder('—')
                                            ->columnSpanFull(),
                                    ])->columns(2),

                                // Bu ürünün diğer ilanları
                                Infolists\Components\Section::make('Bu Ürünün Tüm İlanları')
                                    ->schema([
                                        Infolists\Components\RepeatableEntry::make('product.offers')
                                            ->label('')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('seller.business_name')
                                                    ->label('Satıcı'),
                                                Infolists\Components\TextEntry::make('price')
                                                    ->label('Fiyat')
                                                    ->money('TRY'),
                                                Infolists\Components\TextEntry::make('stock')
                                                    ->label('Stok')
                                                    ->badge()
                                                    ->color(fn (int $state): string => match (true) {
                                                        $state <= 0 => 'danger',
                                                        $state <= 10 => 'warning',
                                                        default => 'success',
                                                    }),
                                                Infolists\Components\TextEntry::make('status')
                                                    ->label('Durum')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'active' => 'success',
                                                        'pending' => 'warning',
                                                        'inactive' => 'gray',
                                                        'sold_out', 'rejected' => 'danger',
                                                        default => 'gray',
                                                    })
                                                    ->formatStateUsing(fn (string $state): string => Offer::STATUS_LABELS[$state] ?? $state),
                                                Infolists\Components\TextEntry::make('created_at')
                                                    ->label('Tarih')
                                                    ->dateTime('d.m.Y'),
                                            ])
                                            ->columns(5),
                                    ]),
                            ])->columnSpan(2),

                        // Sag (1/3) - Satıcı + Durum
                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\Section::make('Satıcı')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('seller.business_name')
                                            ->label('İsim'),
                                        Infolists\Components\TextEntry::make('seller.email')
                                            ->label('E-posta')
                                            ->copyable(),
                                        Infolists\Components\TextEntry::make('seller.phone')
                                            ->label('Telefon')
                                            ->copyable(),
                                        Infolists\Components\TextEntry::make('seller.role')
                                            ->label('Tip')
                                            ->badge()
                                            ->color(fn (?string $state): string => match ($state) {
                                                'retailer' => 'success',
                                                'seller' => 'info',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                'retailer' => 'Kırtasiyeci',
                                                'seller' => 'Tedarikçi',
                                                'super-admin' => 'Süper Admin',
                                                default => $state ?? '—',
                                            }),
                                    ]),

                                Infolists\Components\Section::make('Durum')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('status')
                                            ->label('İlan Durumu')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'active' => 'success',
                                                'pending' => 'warning',
                                                'inactive' => 'gray',
                                                'sold_out', 'rejected' => 'danger',
                                                default => 'gray',
                                            })
                                            ->formatStateUsing(fn (string $state): string => Offer::STATUS_LABELS[$state] ?? $state),
                                        Infolists\Components\TextEntry::make('rejection_reason')
                                            ->label('Ret Sebebi')
                                            ->visible(fn ($record) => $record->status === 'rejected'),
                                        Infolists\Components\TextEntry::make('reviewer.business_name')
                                            ->label('İnceleyen')
                                            ->placeholder('—'),
                                        Infolists\Components\TextEntry::make('reviewed_at')
                                            ->label('İncelenme Tarihi')
                                            ->dateTime('d.m.Y H:i')
                                            ->placeholder('—'),
                                        Infolists\Components\TextEntry::make('created_at')
                                            ->label('Oluşturulma')
                                            ->dateTime('d.m.Y H:i'),
                                        Infolists\Components\TextEntry::make('updated_at')
                                            ->label('Güncelleme')
                                            ->dateTime('d.m.Y H:i'),
                                    ]),
                            ])->columnSpan(1),
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
            'index' => Pages\ListListings::route('/'),
            'view' => Pages\ViewListing::route('/{record}'),
            'edit' => Pages\EditListing::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['product', 'seller', 'product.activeOffers', 'product.category']);
    }
}
