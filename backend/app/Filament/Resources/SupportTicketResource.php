<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Destek Talepleri';

    protected static ?string $modelLabel = 'Destek Talebi';

    protected static ?string $pluralModelLabel = 'Destek Talepleri';

    protected static ?string $navigationGroup = 'Destek';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::open()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.business_name')
                    ->label('Kullanıcı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Konu')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn($record) => $record->subject),
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Kategori')
                    ->colors([
                        'primary' => 'order',
                        'success' => 'payment',
                        'info' => 'shipping',
                        'warning' => 'product',
                        'danger' => 'account',
                        'gray' => 'other',
                    ])
                    ->formatStateUsing(fn(string $state): string => SupportTicket::CATEGORY_LABELS[$state] ?? $state),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'info' => 'open',
                        'warning' => 'in_progress',
                        'primary' => 'waiting',
                        'success' => 'resolved',
                        'gray' => 'closed',
                    ])
                    ->formatStateUsing(fn(string $state): string => SupportTicket::STATUS_LABELS[$state] ?? $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(SupportTicket::STATUS_LABELS),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(SupportTicket::CATEGORY_LABELS),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('changeStatus')
                    ->label('Durum Güncelle')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Yeni Durum')
                            ->options(SupportTicket::STATUS_LABELS)
                            ->required(),
                    ])
                    ->action(function (SupportTicket $record, array $data) {
                        $updates = ['status' => $data['status']];
                        if ($data['status'] === 'resolved') {
                            $updates['resolved_at'] = now();
                        } elseif ($data['status'] === 'closed') {
                            $updates['closed_at'] = now();
                        }
                        $record->update($updates);

                        Notification::make()
                            ->title('Durum güncellendi')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Talep Bilgileri')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.business_name')
                            ->label('Kullanıcı'),
                        Infolists\Components\TextEntry::make('subject')
                            ->label('Konu'),
                        Infolists\Components\TextEntry::make('category')
                            ->label('Kategori')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => SupportTicket::CATEGORY_LABELS[$state] ?? $state),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Durum')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'open' => 'info',
                                'in_progress' => 'warning',
                                'waiting' => 'primary',
                                'resolved' => 'success',
                                'closed' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => SupportTicket::STATUS_LABELS[$state] ?? $state),
                        Infolists\Components\TextEntry::make('order.order_number')
                            ->label('İlgili Sipariş')
                            ->placeholder('Yok')
                            ->prefix('#'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Oluşturma Tarihi')
                            ->dateTime('d.m.Y H:i'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Açıklama')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('admin_note')
                            ->label('Admin Notu')
                            ->placeholder('Yok')
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
        ];
    }
}
