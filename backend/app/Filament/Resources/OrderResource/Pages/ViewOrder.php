<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('confirm')
                ->label('Onayla')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn() => $this->record->status === 'pending')
                ->action(fn() => $this->record->update(['status' => 'confirmed'])),
            Actions\Action::make('ship')
                ->label('Kargoya Ver')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->visible(fn() => in_array($this->record->status, ['confirmed', 'processing']))
                ->action(fn() => $this->record->update([
                    'status' => 'shipped',
                    'shipping_status' => 'shipped',
                    'shipped_at' => now(),
                ])),
            Actions\Action::make('deliver')
                ->label('Teslim Edildi')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn() => $this->record->status === 'shipped')
                ->action(fn() => $this->record->update([
                    'status' => 'delivered',
                    'shipping_status' => 'delivered',
                    'delivered_at' => now(),
                ])),
            Actions\Action::make('cancel')
                ->label('İptal Et')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn() => $this->record->canBeCancelled())
                ->requiresConfirmation()
                ->action(fn() => $this->record->cancel()),
        ];
    }
}
