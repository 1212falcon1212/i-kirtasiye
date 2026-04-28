<?php

namespace App\Filament\Resources\SellerWalletResource\Pages;

use App\Filament\Resources\SellerWalletResource;
use Filament\Resources\Pages\ViewRecord;
use App\Models\WalletTransaction;

class ViewSellerWallet extends ViewRecord
{
    protected static string $resource = SellerWalletResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
