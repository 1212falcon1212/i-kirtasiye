<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Ürünler';

    protected static ?string $modelLabel = 'Ürün';

    protected static ?string $pluralModelLabel = 'Ürünler';

    protected static ?string $navigationGroup = 'Ürün Yönetimi';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Temel Bilgiler')
                    ->schema([
                        Forms\Components\TextInput::make('barcode')
                            ->label('Barkod')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(14),
                        Forms\Components\TextInput::make('name')
                            ->label('Ürün Adı')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('brand')
                            ->label('Marka')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('manufacturer')
                            ->label('Üretici')
                            ->maxLength(255),
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Kategori Adı')
                                    ->required(),
                                Forms\Components\TextInput::make('commission_rate')
                                    ->label('Komisyon Oranı')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0),
                            ]),
                        Forms\Components\TextInput::make('psf')
                            ->label('PSF (Piyasa Satış Fiyatı)')
                            ->numeric()
                            ->prefix('₺')
                            ->step(0.01)
                            ->minValue(0),
                    ])->columns(2),

                Forms\Components\Section::make('Detaylar')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(3)
                            ->maxLength(1000),
                        Forms\Components\FileUpload::make('image')
                            ->label('Görsel')
                            ->image()
                            ->directory('products')
                            ->visibility('public'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Görsel')
                    ->circular(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Barkod')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Ürün Adı')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('brand')
                    ->label('Marka')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('psf')
                    ->label('PSF')
                    ->money('TRY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.commission_rate')
                    ->label('Komisyon')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('offers_count')
                    ->label('Teklif Sayısı')
                    ->counts('offers')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Eklenme Tarihi')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif Durumu'),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
            ])
            ->actions([
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

