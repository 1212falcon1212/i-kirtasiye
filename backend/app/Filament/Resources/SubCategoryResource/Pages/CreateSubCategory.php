<?php

namespace App\Filament\Resources\SubCategoryResource\Pages;

use App\Filament\Resources\SubCategoryResource;
use App\Models\Category;
use Filament\Resources\Pages\CreateRecord;

class CreateSubCategory extends CreateRecord
{
    protected static string $resource = SubCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * When creating a new subcategory, inherit commission rates from parent if not set
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (!empty($data['parent_id'])) {
            $parent = Category::find($data['parent_id']);

            if ($parent) {
                // Inherit commission rate from parent if not explicitly set or is 0
                if (empty($data['commission_rate']) || $data['commission_rate'] == 0) {
                    $data['commission_rate'] = $parent->commission_rate;
                }

                // Inherit VAT rate from parent if not explicitly set
                if (empty($data['vat_rate'])) {
                    $data['vat_rate'] = $parent->vat_rate;
                }

                // Inherit withholding tax rate from parent if not explicitly set
                if (empty($data['withholding_tax_rate'])) {
                    $data['withholding_tax_rate'] = $parent->withholding_tax_rate;
                }
            }
        }

        return $data;
    }
}
