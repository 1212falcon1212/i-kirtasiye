<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class CompanyResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tedarikçiler';

    protected static ?string $modelLabel = 'Tedarikçi';

    protected static ?string $pluralModelLabel = 'Tedarikçiler';

    protected static ?string $navigationGroup = 'Kullanıcılar';

    protected static ?int $navigationSort = 2;

    /**
     * Filter to only show seller users (tedarikçiler).
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', User::ROLE_SELLER);
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

                Forms\Components\Section::make('Tedarikçi Bilgileri')
                    ->schema([
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
                        Forms\Components\Hidden::make('role')
                            ->default(User::ROLE_SELLER),
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
                    ])->columns(2),

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
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
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
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Doğrulandı')
                    ->boolean(),
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
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->verification_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Tedarikçi Onayı')
                    ->modalDescription('Bu tedarikçiyi onaylamak istediğinize emin misiniz?')
                    ->action(function ($record) {
                        $record->update([
                            'verification_status' => 'approved',
                            'is_verified' => true,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Tedarikçi onaylandı')
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
                        $record->update([
                            'verification_status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'rejected_by' => auth()->id(),
                            'rejected_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Tedarikçi başvurusu reddedildi')
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
                                    $record->update([
                                        'verification_status' => 'approved',
                                        'is_verified' => true,
                                        'approved_by' => auth()->id(),
                                        'approved_at' => now(),
                                    ]);
                                }
                            }
                            Notification::make()
                                ->title('Seçili tedarikçiler onaylandı')
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
