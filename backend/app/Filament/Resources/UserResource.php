<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Mail\SellerApprovedMail;
use App\Mail\SellerRejectedMail;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Kırtasiyeciler';

    protected static ?string $modelLabel = 'Kırtasiyeci';

    protected static ?string $pluralModelLabel = 'Kırtasiyeciler';

    protected static ?string $navigationGroup = 'Kullanıcılar';

    protected static ?int $navigationSort = 1;

    /**
     * Filter to only show retailer users (kırtasiyeciler).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', User::ROLE_RETAILER);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Hesap Bilgileri')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Kırtasiyeci Bilgileri')
                    ->schema([
                        Forms\Components\TextInput::make('vergi_no')
                            ->label('Vergi Numarası')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(11)
                            ->minLength(10),
                        Forms\Components\TextInput::make('business_name')
                            ->label('Firma Adı')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nickname')
                            ->label('Rumuz')
                            ->helperText('Sitede görünecek isim')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('city')
                            ->label('Şehir')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('address')
                            ->label('Adres')
                            ->rows(3)
                            ->maxLength(500),
                    ])->columns(2),

                Forms\Components\Section::make('Ticari Bilgiler')
                    ->schema([
                        Forms\Components\TextInput::make('trade_name')
                            ->label('Ticari Ünvan')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('tax_number')
                            ->label('Vergi No')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('tax_office')
                            ->label('Vergi Dairesi')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('mersis_no')
                            ->label('MERSIS No')
                            ->maxLength(20),
                        Forms\Components\TextInput::make('kep_address')
                            ->label('KEP Adresi')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Yetki ve Durum')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Rol')
                            ->options([
                                User::ROLE_RETAILER => 'Kırtasiyeci',
                                User::ROLE_SELLER => 'Tedarikçi',
                                User::ROLE_SUPER_ADMIN => 'Süper Admin',
                            ])
                            ->required()
                            ->default(User::ROLE_RETAILER),
                        Forms\Components\Select::make('verification_status')
                            ->label('Doğrulama Durumu')
                            ->options([
                                'pending' => 'Onay Bekliyor',
                                'approved' => 'Onaylandı',
                                'rejected' => 'Reddedildi',
                            ])
                            ->default('pending'),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Doğrulandı')
                            ->default(false),
                    ])->columns(3),

                Forms\Components\Section::make('Belgeler')
                    ->schema([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Ret Sebebi')
                            ->rows(3)
                            ->visible(fn ($record) => $record?->verification_status === 'rejected'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business_name')
                    ->label('Firma Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nickname')
                    ->label('Rumuz')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('vergi_no')
                    ->label('Vergi No')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Şehir')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('verification_status')
                    ->label('Durum')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => User::VERIFICATION_STATUS_LABELS[$state] ?? $state),
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Rol')
                    ->colors([
                        'success' => User::ROLE_RETAILER,
                        'info' => User::ROLE_SELLER,
                        'warning' => User::ROLE_SUPER_ADMIN,
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        User::ROLE_RETAILER => 'Kırtasiyeci',
                        User::ROLE_SELLER => 'Tedarikçi',
                        User::ROLE_SUPER_ADMIN => 'Süper Admin',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('Doğrulama Durumu')
                    ->options([
                        'pending' => 'Onay Bekliyor',
                        'approved' => 'Onaylandı',
                        'rejected' => 'Reddedildi',
                    ]),
                Tables\Filters\SelectFilter::make('role')
                    ->label('Rol')
                    ->options([
                        User::ROLE_RETAILER => 'Kırtasiyeci',
                        User::ROLE_SELLER => 'Tedarikçi',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->verification_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Hesap Onayı')
                    ->modalDescription('Bu hesabı onaylamak istediğinize emin misiniz? Kullanıcıya hoş geldin e-postası gönderilecektir.')
                    ->action(function ($record) {
                        $record->approve(auth()->id());

                        Mail::to($record->email)->queue(new SellerApprovedMail($record));

                        Notification::make()
                            ->title('Hesap onaylandı')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reddet')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->verification_status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Ret Sebebi')
                            ->required()
                            ->rows(3)
                            ->placeholder('Lütfen ret sebebini belirtiniz...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject($data['rejection_reason'], auth()->id());

                        Mail::to($record->email)->queue(new SellerRejectedMail($record, $data['rejection_reason']));

                        Notification::make()
                            ->title('Başvuru reddedildi')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\Action::make('viewDocuments')
                    ->label('Belgeler')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn ($record) => $record->sellerDocuments()->count() > 0)
                    ->modalHeading('Yüklenen Belgeler')
                    ->modalContent(fn ($record) => view('filament.modals.seller-documents', ['documents' => $record->sellerDocuments]))
                    ->modalSubmitAction(false),

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
                            foreach ($records as $record) {
                                if ($record->verification_status === 'pending') {
                                    $record->approve(auth()->id());
                                    Mail::to($record->email)->queue(new SellerApprovedMail($record));
                                }
                            }
                            Notification::make()
                                ->title('Seçili hesaplar onaylandı')
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
            UserResource\RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
