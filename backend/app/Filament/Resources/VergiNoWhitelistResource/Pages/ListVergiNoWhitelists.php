<?php

namespace App\Filament\Resources\VergiNoWhitelistResource\Pages;

use App\Filament\Resources\VergiNoWhitelistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVergiNoWhitelists extends ListRecords
{
    protected static string $resource = VergiNoWhitelistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
