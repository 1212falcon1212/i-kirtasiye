<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Ana Kategoriler';

    protected static ?string $modelLabel = 'Ana Kategori';

    protected static ?string $pluralModelLabel = 'Ana Kategoriler';

    protected static ?string $navigationGroup = 'Ürün Yönetimi';

    protected static ?int $navigationSort = 1;

    /**
     * Only show main categories (parent_id = null)
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNull('parent_id');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Kategori Bilgileri')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Kategori Adı')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Textarea::make('description')
                            ->label('Açıklama')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Komisyon Ayarları')
                    ->schema([
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Komisyon Oranı')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Bu kategorideki ürünlerin satışından alınacak komisyon oranı')
                            ->required(),

                        Forms\Components\Toggle::make('propagate_commission')
                            ->label('Alt Kategorilere Uygula')
                            ->default(true)
                            ->helperText('Komisyon oranı değiştiğinde tüm alt kategorilere otomatik uygulansın')
                            ->dehydrated(false)
                            ->visible(fn (?Category $record) => $record && $record->children()->count() > 0),
                    ]),

                Forms\Components\Section::make('Vergi Ayarları')
                    ->description('Kategori bazlı KDV ve stopaj oranları')
                    ->schema([
                        Forms\Components\TextInput::make('vat_rate')
                            ->label('KDV Oranı')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(20)
                            ->helperText('Standart: %20, İndirimli: %10, %1'),

                        Forms\Components\TextInput::make('withholding_tax_rate')
                            ->label('Stopaj Oranı')
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->default(0)
                            ->helperText('Stopaj kesintisi varsa belirtin (genellikle %0)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Durum')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Pasif kategoriler ürün listelerinde görünmez'),

                        Forms\Components\Toggle::make('show_on_homepage')
                            ->label('Ana Sayfada Göster')
                            ->default(false)
                            ->helperText('Bu kategoriyi ana sayfada ayrı bir bölüm olarak gösterir'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Kategori Adı')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Komisyon')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state == 0 => 'gray',
                        $state <= 5 => 'success',
                        $state <= 10 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('children_count')
                    ->label('Alt Kategori')
                    ->counts('children')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('products_count')
                    ->label('Ürün Sayısı')
                    ->counts('products')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Durum')
                    ->boolean(),

                Tables\Columns\IconColumn::make('show_on_homepage')
                    ->label('Ana Sayfa')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Durum')
                    ->placeholder('Tümü')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleHomepage')
                    ->label(fn ($record) => $record->show_on_homepage ? 'Ana Sayfadan Kaldır' : 'Ana Sayfaya Ekle')
                    ->icon(fn ($record) => $record->show_on_homepage ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($record) => $record->show_on_homepage ? 'danger' : 'success')
                    ->action(function ($record) {
                        $record->update(['show_on_homepage' => ! $record->show_on_homepage]);
                        \Illuminate\Support\Facades\Cache::forget('cms.homepage.category_sections');
                    })
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->show_on_homepage ? 'Ana Sayfadan Kaldır' : 'Ana Sayfaya Ekle')
                    ->modalDescription(fn ($record) => $record->show_on_homepage
                        ? "\"{$record->name}\" kategorisi ana sayfadan kaldırılacak."
                        : "\"{$record->name}\" kategorisi ana sayfada gösterilecek."),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enableHomepage')
                        ->label('Ana Sayfaya Ekle')
                        ->icon('heroicon-o-eye')
                        ->action(function ($records) {
                            $records->each->update(['show_on_homepage' => true]);
                            \Illuminate\Support\Facades\Cache::forget('cms.homepage.category_sections');
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('disableHomepage')
                        ->label('Ana Sayfadan Kaldır')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['show_on_homepage' => false]);
                            \Illuminate\Support\Facades\Cache::forget('cms.homepage.category_sections');
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
