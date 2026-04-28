<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VergiNoWhitelistResource\Pages;
use App\Models\VergiNoWhitelist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VergiNoWhitelistResource extends Resource
{
    protected static ?string $model = VergiNoWhitelist::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Vergi No Whitelist';

    protected static ?string $modelLabel = 'Vergi No Kaydı';

    protected static ?string $pluralModelLabel = 'Vergi No Whitelist';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vergi No Bilgileri')
                    ->schema([
                        Forms\Components\TextInput::make('vergi_no')
                            ->label('Vergi Numarası')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(11)
                            ->minLength(10)
                            ->numeric(),
                        Forms\Components\TextInput::make('company_name')
                            ->label('Firma Adı')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->label('Şehir')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('district')
                            ->label('İlçe')
                            ->maxLength(100),
                        Forms\Components\Textarea::make('address')
                            ->label('Adres')
                            ->rows(2)
                            ->maxLength(500),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Forms\Components\Toggle::make('is_used')
                            ->label('Kullanıldı')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('used_by_user_id')
                            ->label('Kullanan Kullanıcı ID')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vergi_no')
                    ->label('Vergi No')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Firma Adı')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Şehir')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('district')
                    ->label('İlçe')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_used')
                    ->label('Kullanıldı')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Eklenme Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktif Durumu'),
                Tables\Filters\TernaryFilter::make('is_used')
                    ->label('Kullanım Durumu'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('import')
                    ->label('Excel\'den İçe Aktar')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        Forms\Components\FileUpload::make('file')
                            ->label('Excel Dosyası')
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                            ])
                            ->disk('local')
                            ->directory('imports'),
                    ])
                    ->action(function (array $data): void {
                        $path = storage_path('app/' . $data['file']);
                        $import = new \App\Imports\VergiNoWhitelistImport();
                        \Maatwebsite\Excel\Facades\Excel::import($import, $path);

                        \Filament\Notifications\Notification::make()
                            ->title('İçe aktarma tamamlandı')
                            ->body("Eklenen: {$import->getImportedCount()}, Atlanan: {$import->getSkippedCount()}")
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListVergiNoWhitelists::route('/'),
            'create' => Pages\CreateVergiNoWhitelist::route('/create'),
            'edit' => Pages\EditVergiNoWhitelist::route('/{record}/edit'),
        ];
    }
}
