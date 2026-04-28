<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Yorum Moderasyonu';

    protected static ?string $modelLabel = 'Yorum';

    protected static ?string $pluralModelLabel = 'Yorumlar';

    protected static ?string $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 4;

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
                Forms\Components\Section::make('Yorum Bilgileri')
                    ->schema([
                        Forms\Components\Select::make('buyer_id')
                            ->label('Alıcı')
                            ->relationship('buyer', 'business_name')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                        Forms\Components\Select::make('seller_id')
                            ->label('Satıcı')
                            ->relationship('seller', 'business_name')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                        Forms\Components\Select::make('product_id')
                            ->label('Ürün')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->disabled(),
                    ])->columns(3),

                Forms\Components\Section::make('Puanlar')
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->label('Genel Puan')
                            ->numeric()
                            ->disabled()
                            ->suffix('/5'),
                        Forms\Components\TextInput::make('delivery_rating')
                            ->label('Teslimat')
                            ->numeric()
                            ->disabled()
                            ->suffix('/5'),
                        Forms\Components\TextInput::make('quality_rating')
                            ->label('Kalite')
                            ->numeric()
                            ->disabled()
                            ->suffix('/5'),
                        Forms\Components\TextInput::make('communication_rating')
                            ->label('İletişim')
                            ->numeric()
                            ->disabled()
                            ->suffix('/5'),
                    ])->columns(4),

                Forms\Components\Section::make('Yorum İçeriği')
                    ->schema([
                        Forms\Components\Textarea::make('comment')
                            ->label('Yorum')
                            ->rows(4)
                            ->disabled(),
                        Forms\Components\Textarea::make('seller_reply')
                            ->label('Satıcı Yanıtı')
                            ->rows(3)
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Moderasyon')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options(Review::STATUS_LABELS)
                            ->required(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Ret Sebebi')
                            ->rows(3)
                            ->visible(fn($get) => $get('status') === 'rejected'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Ürün')
                    ->searchable()
                    ->sortable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('buyer.business_name')
                    ->label('Alıcı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seller.business_name')
                    ->label('Satıcı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Puan')
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn($state) => "★ {$state}"),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Yorum')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->comment),
                Tables\Columns\IconColumn::make('seller_reply')
                    ->label('Yanıt')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn(string $state): string => Review::STATUS_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(Review::STATUS_LABELS)
                    ->default('pending'),
                Tables\Filters\SelectFilter::make('rating')
                    ->label('Puan')
                    ->options([
                        '5' => '★★★★★ (5)',
                        '4' => '★★★★ (4)',
                        '3' => '★★★ (3)',
                        '2' => '★★ (2)',
                        '1' => '★ (1)',
                    ]),
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
                    ->modalHeading('Yorumu Onayla')
                    ->modalDescription('Bu yorumu onaylamak istediğinize emin misiniz? Yorum sitede yayınlanacaktır.')
                    ->action(function ($record) {
                        $record->approve(auth()->id());

                        Notification::make()
                            ->title('Yorum onaylandı')
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
                        $record->reject(auth()->id(), $data['rejection_reason']);

                        Notification::make()
                            ->title('Yorum reddedildi')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
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
                                    $record->approve(auth()->id());
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->title("{$count} yorum onaylandı")
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
            'index' => Pages\ListReviews::route('/'),
            'view' => Pages\ViewReview::route('/{record}'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
