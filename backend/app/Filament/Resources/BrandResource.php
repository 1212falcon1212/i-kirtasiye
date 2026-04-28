<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Filament Marka Yonetimi Resource
 */
class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Markalar';

    protected static ?string $modelLabel = 'Marka';

    protected static ?string $pluralModelLabel = 'Markalar';

    protected static ?string $navigationGroup = 'CMS';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Marka Bilgileri')
                    ->description('Marka temel bilgilerini girin')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Marka Adi')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $state, Forms\Set $set) {
                                $set('slug', Str::slug($state));
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('URL icin benzersiz kimlik'),

                        Forms\Components\FileUpload::make('logo_url')
                            ->label('Logo')
                            ->image()
                            ->directory('brands')
                            ->imagePreviewHeight('100')
                            ->columnSpanFull()
                            ->helperText('Marka logosu (PNG veya SVG tercih edilir)'),

                        Forms\Components\Textarea::make('description')
                            ->label('Aciklama')
                            ->maxLength(1000)
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('website_url')
                            ->label('Web Sitesi')
                            ->url()
                            ->maxLength(255)
                            ->prefix('https://')
                            ->placeholder('www.example.com'),
                    ])->columns(2),

                Forms\Components\Section::make('Ayarlar')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Pasif markalar frontend\'de gosterilmez'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('One Cikan')
                            ->default(false)
                            ->helperText('Ana sayfada marka bolumunde gosterilir'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Siralama')
                            ->numeric()
                            ->default(0)
                            ->helperText('Kucuk degerler once gosterilir'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->height(40)
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Marka Adi')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('One Cikan')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sira')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Olusturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('One Cikan'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
