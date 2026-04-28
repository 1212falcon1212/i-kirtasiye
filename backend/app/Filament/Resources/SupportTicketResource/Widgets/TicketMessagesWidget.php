<?php

namespace App\Filament\Resources\SupportTicketResource\Widgets;

use App\Models\SupportTicketMessage;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Storage;

class TicketMessagesWidget extends TableWidget
{
    public $record;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Mesajlar';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SupportTicketMessage::query()
                    ->where('ticket_id', $this->record->id)
                    ->with('user:id,business_name,role')
                    ->orderBy('created_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.business_name')
                    ->label('Gönderen')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->is_staff_reply ? "🛡️ {$state} (Destek)" : $state;
                    }),
                Tables\Columns\TextColumn::make('message')
                    ->label('Mesaj')
                    ->wrap()
                    ->limit(200),
                Tables\Columns\TextColumn::make('attachments')
                    ->label('Ekler')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record->attachments || count($record->attachments) === 0) {
                            return '-';
                        }
                        return collect($record->attachments)->map(function ($att) {
                            $url = Storage::disk('public')->url($att['path']);
                            return '<a href="' . e($url) . '" target="_blank" class="text-primary-600 underline text-xs">' . e($att['name']) . '</a>';
                        })->implode('<br>');
                    })
                    ->html(),
                Tables\Columns\IconColumn::make('is_staff_reply')
                    ->label('Destek')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarih')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->paginated(false)
            ->defaultSort('created_at', 'asc');
    }
}
