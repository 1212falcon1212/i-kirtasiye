<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SellerDocumentResource\Pages;
use App\Models\SellerDocument;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class SellerDocumentResource extends Resource
{
    protected static ?string $model = SellerDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Belge Onayları';

    protected static ?string $modelLabel = 'Belge';

    protected static ?string $pluralModelLabel = 'Belgeler';

    protected static ?string $navigationGroup = 'Kullanıcılar';

    protected static ?int $navigationSort = 3;

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
                Forms\Components\Section::make('Belge Bilgileri')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Kullanıcı')
                            ->relationship('user', 'business_name')
                            ->searchable()
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('type')
                            ->label('Belge Türü')
                            ->options(SellerDocument::TYPE_LABELS)
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options([
                                'pending' => 'Beklemede',
                                'approved' => 'Onaylandı',
                                'rejected' => 'Reddedildi',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Ret Sebebi')
                            ->rows(3)
                            ->visible(fn($get) => $get('status') === 'rejected')
                            ->requiredIf('status', 'rejected'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.business_name')
                    ->label('Kullanıcı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('E-posta')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('user.role')
                    ->label('Hesap Tipi')
                    ->colors([
                        'success' => 'retailer',
                        'info' => 'seller',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'retailer' => 'Kırtasiyeci',
                        'seller' => 'Tedarikçi',
                        'super-admin' => 'Süper Admin',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('type')
                    ->label('Belge Türü')
                    ->formatStateUsing(fn($state) => SellerDocument::TYPE_LABELS[$state] ?? $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('original_name')
                    ->label('Dosya Adı')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn($state) => match($state) {
                        'pending' => 'Beklemede',
                        'approved' => 'Onaylandı',
                        'rejected' => 'Reddedildi',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Yüklenme Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'pending' => 'Beklemede',
                        'approved' => 'Onaylandı',
                        'rejected' => 'Reddedildi',
                    ])
                    ->default('pending'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Belge Türü')
                    ->options(SellerDocument::TYPE_LABELS),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Görüntüle')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn($record) => Storage::url($record->file_path))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('approve')
                    ->label('Onayla')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Belgeyi Onayla')
                    ->modalDescription('Bu belgeyi onaylamak istediğinize emin misiniz?')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'rejection_reason' => null,
                        ]);

                        // Check if all required documents are approved
                        $user = $record->user;
                        if ($user && $user->documents_approved) {
                            $user->update([
                                'verification_status' => 'approved',
                                'is_verified' => true,
                                'approved_at' => now(),
                                'approved_by' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title('Belge onaylandı ve kullanıcı aktifleştirildi')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Belge onaylandı')
                                ->body('Kullanıcının diğer belgeleri henüz onaylanmadı.')
                                ->success()
                                ->send();
                        }
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
                            ->placeholder('Belgenin neden reddedildiğini açıklayın...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Belge reddedildi')
                            ->warning()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkApprove')
                        ->label('Toplu Onayla')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $approvedCount = 0;
                            $userIds = [];

                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'approved',
                                        'reviewed_by' => auth()->id(),
                                        'reviewed_at' => now(),
                                    ]);
                                    $approvedCount++;
                                    $userIds[] = $record->user_id;
                                }
                            }

                            // Check and activate users whose all documents are approved
                            $activatedUsers = 0;
                            foreach (array_unique($userIds) as $userId) {
                                $user = User::find($userId);
                                if ($user && $user->documents_approved) {
                                    $user->update([
                                        'verification_status' => 'approved',
                                        'is_verified' => true,
                                        'approved_at' => now(),
                                        'approved_by' => auth()->id(),
                                    ]);
                                    $activatedUsers++;
                                }
                            }

                            Notification::make()
                                ->title("{$approvedCount} belge onaylandı" . ($activatedUsers > 0 ? ", {$activatedUsers} kullanıcı aktifleştirildi" : ""))
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListSellerDocuments::route('/'),
        ];
    }
}
