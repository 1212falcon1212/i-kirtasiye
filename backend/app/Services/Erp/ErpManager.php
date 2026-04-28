<?php

namespace App\Services\Erp;

use App\Interfaces\ErpIntegrationInterface;
use App\Models\UserIntegration;
use App\Services\Erp\Providers\EntegraProvider;
use App\Services\Erp\Providers\BizimHesapProvider;
use App\Services\Erp\Providers\SentosProvider;
use App\Services\Erp\Providers\ParasutProvider;
use App\Services\Erp\Providers\StockMountProvider;
use App\Services\Erp\Providers\DopigoProvider;
use App\Services\Erp\Providers\KolaySoftProvider;
use InvalidArgumentException;

class ErpManager
{
    /**
     * Get ERP driver instance based on integration settings
     */
    public function getDriver(UserIntegration $integration): ErpIntegrationInterface
    {
        return match ($integration->erp_type) {
            'entegra' => new EntegraProvider($integration),
            'bizimhesap' => new BizimHesapProvider($integration),
            'parasut' => new ParasutProvider($integration),
            'sentos' => new SentosProvider($integration),
            'stockmount' => new StockMountProvider($integration),
            'dopigo' => new DopigoProvider($integration),
            'kolaysoft' => new KolaySoftProvider($integration),
            default => throw new InvalidArgumentException("Unsupported ERP type: {$integration->erp_type}"),
        };
    }
}
