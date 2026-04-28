<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\SellerApprovedMail;
use App\Mail\SellerRejectedMail;
use App\Models\SellerDocument;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label('Onayla')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->verification_status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Hesap Onayı')
                ->modalDescription('Bu hesabı onaylamak istediğinize emin misiniz?')
                ->action(function () {
                    $this->record->approve(auth()->id());
                    Mail::to($this->record->email)->queue(new SellerApprovedMail($this->record));
                    Notification::make()->title('Hesap onaylandı')->success()->send();
                }),

            Actions\Action::make('reject')
                ->label('Reddet')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->verification_status === 'pending')
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Ret Sebebi')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->reject($data['rejection_reason'], auth()->id());
                    Mail::to($this->record->email)->queue(new SellerRejectedMail($this->record, $data['rejection_reason']));
                    Notification::make()->title('Başvuru reddedildi')->warning()->send();
                }),

            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Kırtasiyeci Bilgileri')
                    ->schema([
                        Components\TextEntry::make('business_name')->label('Firma Adı'),
                        Components\TextEntry::make('vergi_no')->label('Vergi Numarası'),
                        Components\TextEntry::make('email')->label('E-posta'),
                        Components\TextEntry::make('phone')->label('Telefon'),
                        Components\TextEntry::make('city')->label('Şehir'),
                        Components\TextEntry::make('address')->label('Adres'),
                    ])->columns(3),

                Components\Section::make('Doğrulama Durumu')
                    ->schema([
                        Components\TextEntry::make('verification_status')
                            ->label('Durum')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'warning',
                            })
                            ->formatStateUsing(fn ($state) => \App\Models\User::VERIFICATION_STATUS_LABELS[$state] ?? $state),
                        Components\TextEntry::make('rejection_reason')
                            ->label('Ret Sebebi')
                            ->visible(fn ($record) => $record->verification_status === 'rejected'),
                        Components\TextEntry::make('approved_at')
                            ->label('Onay Tarihi')
                            ->dateTime('d.m.Y H:i'),
                    ])->columns(3),

                Components\Section::make('Yüklenen Belgeler')
                    ->schema([
                        Components\RepeatableEntry::make('sellerDocuments')
                            ->label('')
                            ->schema([
                                Components\TextEntry::make('type')
                                    ->label('Belge Tipi')
                                    ->formatStateUsing(fn ($state) => SellerDocument::TYPE_LABELS[$state] ?? $state),
                                Components\TextEntry::make('original_name')->label('Dosya Adı'),
                                Components\TextEntry::make('status')
                                    ->label('Durum')
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'warning',
                                    })
                                    ->formatStateUsing(fn ($state) => SellerDocument::STATUS_LABELS[$state] ?? $state),
                                Components\TextEntry::make('rejection_reason')
                                    ->label('Ret Sebebi')
                                    ->visible(fn ($record) => $record->status === 'rejected'),
                                Components\TextEntry::make('created_at')
                                    ->label('Yüklenme')
                                    ->dateTime('d.m.Y H:i'),
                            ])
                            ->columns(5)
                            ->contained(false),
                    ]),
            ]);
    }

    public function getRelationManagers(): array
    {
        return [
            UserResource\RelationManagers\DocumentsRelationManager::class,
        ];
    }
}
