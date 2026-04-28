<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\SellerDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'sellerDocuments';

    protected static ?string $title = 'Belgeler';

    protected static ?string $modelLabel = 'Belge';

    protected static ?string $pluralModelLabel = 'Belgeler';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Belge Tipi')
                    ->options(SellerDocument::TYPE_LABELS)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Durum')
                    ->options(SellerDocument::STATUS_LABELS)
                    ->required(),
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Ret Sebebi')
                    ->rows(2)
                    ->visible(fn($get) => $get('status') === 'rejected'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Belge Tipi')
                    ->formatStateUsing(fn($state) => SellerDocument::TYPE_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('original_name')
                    ->label('Dosya Adı')
                    ->limit(30),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn($state) => SellerDocument::STATUS_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Yüklenme')
                    ->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('İnceleme')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(SellerDocument::STATUS_LABELS),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Görüntüle')
                    ->icon('heroicon-o-eye')
                    ->url(fn($record) => asset('storage/' . $record->file_path), shouldOpenInNewTab: true),

                Tables\Actions\Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status !== 'approved')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->approve(auth()->id());
                        Notification::make()->title('Belge onaylandı')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status !== 'rejected')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Ret Sebebi')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject($data['rejection_reason'], auth()->id());
                        Notification::make()->title('Belge reddedildi')->warning()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approveAll')
                    ->label('Tümünü Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $record->approve(auth()->id());
                        }
                        Notification::make()->title('Belgeler onaylandı')->success()->send();
                    }),
            ]);
    }
}
