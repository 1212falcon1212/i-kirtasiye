<?php

namespace App\Services\Payments;

use App\Interfaces\PaymentGatewayInterface;
use App\Models\Setting;
use InvalidArgumentException;

class PaymentManager
{
    /**
     * Get the active payment gateway provider
     */
    public function getProvider(): ?PaymentGatewayInterface
    {
        $activeGateway = Setting::getValue('payment.active_gateway', 'none');

        return match ($activeGateway) {
            'iyzico' => new IyzicoProvider(),
            'paytr' => new PayTRProvider(),
            'none' => null,
            default => null,
        };
    }

    /**
     * Get a specific payment provider by name
     */
    public function getProviderByName(string $name): PaymentGatewayInterface
    {
        return match ($name) {
            'iyzico' => new IyzicoProvider(),
            'paytr' => new PayTRProvider(),
            default => throw new InvalidArgumentException("Unknown payment provider: {$name}"),
        };
    }

    /**
     * Check if payments are enabled
     */
    public function isEnabled(): bool
    {
        $activeGateway = Setting::getValue('payment.active_gateway', 'none');
        return $activeGateway !== 'none';
    }

    /**
     * Get the active gateway name
     */
    public function getActiveGateway(): string
    {
        return Setting::getValue('payment.active_gateway', 'none');
    }

    /**
     * Check if test mode is enabled
     */
    public function isTestMode(): bool
    {
        return Setting::getValue('payment.test_mode', true);
    }

    /**
     * Get available payment gateways
     */
    public function getAvailableGateways(): array
    {
        return [
            'iyzico' => 'Iyzico',
            'paytr' => 'PayTR',
        ];
    }
}
