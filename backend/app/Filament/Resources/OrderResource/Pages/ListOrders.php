<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Dışa Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tümü')
                ->badge(fn() => $this->getModel()::count()),

            'pending' => Tab::make('Beklemede')
                ->badge(fn() => $this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'pending')),

            'confirmed' => Tab::make('Onaylı')
                ->badge(fn() => $this->getModel()::where('status', 'confirmed')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'confirmed')),

            'shipped' => Tab::make('Kargoda')
                ->badge(fn() => $this->getModel()::where('status', 'shipped')->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'shipped')),

            'delivered' => Tab::make('Teslim')
                ->badge(fn() => $this->getModel()::where('status', 'delivered')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'delivered')),

            'cancelled' => Tab::make('İptal')
                ->badge(fn() => $this->getModel()::where('status', 'cancelled')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'cancelled')),
        ];
    }
}
