<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use FilamentTiptapEditor\TiptapEditor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Blog Yazilari';

    protected static ?string $modelLabel = 'Blog Yazisi';

    protected static ?string $pluralModelLabel = 'Blog Yazilari';

    protected static ?string $navigationGroup = 'CMS';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Sol Kolon (2/3)
                        Forms\Components\Section::make('Icerik')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Baslik')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state ?? ''))),
                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('excerpt')
                                    ->label('Ozet')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->helperText('Listeleme sayfasinda gorunecek kisa ozet'),
                                TiptapEditor::make('content')
                                    ->label('Icerik')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(2),

                        // Sag Kolon (1/3)
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Section::make('Yayin Ayarlari')
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->label('Durum')
                                            ->options([
                                                'draft' => 'Taslak',
                                                'published' => 'Yayinda',
                                                'archived' => 'Arsivlendi',
                                            ])
                                            ->default('draft')
                                            ->required(),
                                        Forms\Components\DateTimePicker::make('published_at')
                                            ->label('Yayin Tarihi'),
                                        Forms\Components\Toggle::make('is_featured')
                                            ->label('One Cikan')
                                            ->default(false),
                                    ]),

                                Forms\Components\Section::make('Kategori & Etiketler')
                                    ->schema([
                                        Forms\Components\Select::make('category_id')
                                            ->label('Kategori')
                                            ->options(BlogCategory::active()->ordered()->pluck('name', 'id'))
                                            ->searchable()
                                            ->nullable(),
                                        Forms\Components\TagsInput::make('tags')
                                            ->label('Etiketler')
                                            ->placeholder('Etiket ekle...'),
                                    ]),

                                Forms\Components\Section::make('SEO')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_title')
                                            ->label('Meta Baslik')
                                            ->maxLength(70),
                                        Forms\Components\Textarea::make('meta_description')
                                            ->label('Meta Aciklama')
                                            ->rows(3)
                                            ->maxLength(160),
                                    ]),

                                Forms\Components\Section::make('Gorsel')
                                    ->schema([
                                        Forms\Components\FileUpload::make('featured_image')
                                            ->label('Kapak Gorseli')
                                            ->image()
                                            ->directory('blog')
                                            ->imagePreviewHeight('200')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->maxSize(5120),
                                    ]),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Gorsel')
                    ->height(50)
                    ->circular(false),
                Tables\Columns\TextColumn::make('title')
                    ->label('Baslik')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'published' => 'success',
                        'draft' => 'warning',
                        'archived' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'published' => 'Yayinda',
                        'draft' => 'Taslak',
                        'archived' => 'Arsiv',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Yayin Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('view_count')
                    ->label('Goruntulenme')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('One Cikan')
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'draft' => 'Taslak',
                        'published' => 'Yayinda',
                        'archived' => 'Arsiv',
                    ]),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
