<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Bannerlar';

    protected static ?string $navigationGroup = 'CMS';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Banner Bilgileri')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Görsel')
                            ->image()
                            ->directory('banners')
                            ->required()
                            ->imagePreviewHeight('200')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120) // 5MB
                            ->helperText('Hero: 1920x600 px | Promosyon (2\'li): 700x180 px | Sadece görsel yükleyip metin alanlarını boş bırakabilirsiniz.')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title')
                            ->label('Başlık (Opsiyonel)')
                            ->placeholder('B2B Kırtasiye Pazaryeri')
                            ->helperText('Boş bırakırsanız banner üzerinde metin görünmez')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subtitle')
                            ->label('Alt Başlık (Opsiyonel)')
                            ->placeholder('Binlerce kırtasiye, tek platformda.')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('badge_text')
                            ->label('Badge Metni (Opsiyonel)')
                            ->placeholder('ÖZEL FIRSAT')
                            ->helperText('Üst köşede görünen etiket (ör: ÖZEL FIRSAT, YENİ, KAMPANYA)')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('link_url')
                            ->label('Link URL (Opsiyonel)')
                            ->placeholder('/market/products')
                            ->helperText('Tıklandığında gidilecek sayfa (ör: /market/products veya https://...)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('button_text')
                            ->label('Buton Metni (Opsiyonel)')
                            ->placeholder('Keşfet')
                            ->maxLength(50),
                    ])->columns(2),

                Forms\Components\Section::make('Ayarlar')
                    ->schema([
                        Forms\Components\Select::make('location')
                            ->label('Konum')
                            ->options(Banner::locationOptions())
                            ->required()
                            ->live(),
                        Forms\Components\TextInput::make('tab_name')
                            ->label('Tab Adı')
                            ->placeholder('Kampanyalar')
                            ->helperText('Hero bannerlar için tab etiketi. Aynı tab adına sahip bannerlar aynı grupta döner.')
                            ->maxLength(50)
                            ->visible(fn (Forms\Get $get): bool => $get('location') === 'home_hero'),
                        Forms\Components\ColorPicker::make('bg_color')
                            ->label('Arka Plan Rengi')
                            ->helperText('Banner çerçevesinin arka plan rengi')
                            ->visible(fn (Forms\Get $get): bool => $get('location') === 'home_hero'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sıra')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Başlangıç Tarihi'),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Bitiş Tarihi'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Görsel')
                    ->height(40)
                    ->width(80),
                Tables\Columns\TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->title),
                Tables\Columns\TextColumn::make('location')
                    ->label('Konum')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'home_hero' => 'Hero',
                        'home_promo' => 'Promosyon',
                        'home_middle' => 'Orta',
                        'home_brand' => 'Marka',
                        'home_grid' => 'Grid',
                        'home_bottom' => 'Alt',
                        'home_showcase' => 'Vitrin',
                        'sidebar' => 'Sidebar',
                        'category_top' => 'Kategori',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('tab_name')
                    ->label('Tab')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sıra')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Başlangıç')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Bitiş')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->label('Konum')
                    ->options(Banner::locationOptions()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif'),
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
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
