<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        // Check if propagate_commission toggle is enabled (default true)
        $propagate = $data['propagate_commission'] ?? true;

        if ($propagate && $record->children()->count() > 0) {
            // Update all children's commission rate
            $updatedCount = $record->children()->update([
                'commission_rate' => $record->commission_rate,
                'vat_rate' => $record->vat_rate,
                'withholding_tax_rate' => $record->withholding_tax_rate,
            ]);

            if ($updatedCount > 0) {
                Notification::make()
                    ->title('Alt Kategoriler Güncellendi')
                    ->body("{$updatedCount} alt kategorinin komisyon oranı %{$record->commission_rate} olarak güncellendi.")
                    ->success()
                    ->send();
            }
        }
    }
}
