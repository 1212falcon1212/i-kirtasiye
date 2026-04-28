<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Sözleşme onaylarını yönetir (salt okunur).
 */
class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Sözleşme Onayları';

    protected static ?string $modelLabel = 'Sözleşme Onayı';

    protected static ?string $pluralModelLabel = 'Sözleşme Onayları';

    protected static ?string $navigationGroup = 'CMS';

    protected static ?int $navigationSort = 5;

    public const TYPE_LABELS = [
        'kvkk' => 'KVKK Aydınlatma',
        'distance_sales' => 'Mesafeli Satış Sözleşmesi',
        'membership' => 'Üyelik Sözleşmesi',
        'b2b_sales' => 'B2B Satış Sözleşmesi',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sözleşme Detayları')
                    ->schema([
                        Forms\Components\TextInput::make('user.business_name')
                            ->label('Firma')
                            ->disabled(),
                        Forms\Components\TextInput::make('type')
                            ->label('Sözleşme Türü')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => self::TYPE_LABELS[$state] ?? $state),
                        Forms\Components\TextInput::make('version')
                            ->label('Versiyon')
                            ->disabled(),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Adresi')
                            ->disabled(),
                        Forms\Components\TextInput::make('approved_at')
                            ->label('Onay Tarihi')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d.m.Y H:i:s') : '-'),
                    ])->columns(2),

                Forms\Components\Section::make('Meta Veriler')
                    ->schema([
                        Forms\Components\Textarea::make('metadata')
                            ->label('Metadata (JSON)')
                            ->disabled()
                            ->rows(8)
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state),
                    ])
                    ->visible(fn ($record) => ! empty($record?->metadata)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.business_name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Sözleşme Türü')
                    ->colors([
                        'primary' => 'kvkk',
                        'success' => 'membership',
                        'info' => 'distance_sales',
                        'warning' => 'b2b_sales',
                    ])
                    ->formatStateUsing(fn ($state) => self::TYPE_LABELS[$state] ?? $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->label('Versiyon')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Adresi')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Onay Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Kayıt Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Sözleşme Türü')
                    ->options(self::TYPE_LABELS),
                Tables\Filters\Filter::make('approved_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Başlangıç Tarihi'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Bitiş Tarihi'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('approved_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('approved_at', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Başlangıç: '.\Carbon\Carbon::parse($data['from'])->format('d.m.Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Bitiş: '.\Carbon\Carbon::parse($data['until'])->format('d.m.Y');
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'view' => Pages\ViewContract::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
