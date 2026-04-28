<?php

namespace App\Filament\Resources\VergiNoWhitelistResource\Pages;

use App\Filament\Resources\VergiNoWhitelistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVergiNoWhitelist extends EditRecord
{
    protected static string $resource = VergiNoWhitelistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
