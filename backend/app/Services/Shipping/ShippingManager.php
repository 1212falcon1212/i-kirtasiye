<?php

namespace App\Services\Shipping;

use App\Interfaces\ShippingProviderInterface;
use App\Models\Setting;

/**
 * Shipping Manager - Factory for shipping providers
 */
class ShippingManager
{
    protected array $providers = [];

    public function __construct()
    {
        $this->registerProviders();
    }

    /**
     * Register available shipping providers
     */
    protected function registerProviders(): void
    {
        $this->providers = [
            'aras' => ArasProvider::class,
            'yurtici' => YurtIciProvider::class,
            'mng' => MngProvider::class,
            'sendeo' => SendeoProvider::class,
            'hepsijet' => HepsijetProvider::class,
            'ptt' => PttProvider::class,
            'surat' => SuratProvider::class,
            'kolaygelsin' => KolaygelsinProvider::class,
            'navlungo' => NavlungoProvider::class,
        ];
    }

    /**
     * Get active shipping provider
     */
    public function getProvider(): ?ShippingProviderInterface
    {
        $activeProvider = Setting::getValue('shipping.default_provider', 'none');

        if ($activeProvider === 'none' || !isset($this->providers[$activeProvider])) {
            return null;
        }

        $provider = app($this->providers[$activeProvider]);

        if (!$provider->isAvailable()) {
            return null;
        }

        return $provider;
    }

    /**
     * Get provider by name
     */
    public function getProviderByName(string $name): ?ShippingProviderInterface
    {
        if (!isset($this->providers[$name])) {
            return null;
        }

        return app($this->providers[$name]);
    }

    /**
     * Check if shipping is enabled
     */
    public function isEnabled(): bool
    {
        return $this->getProvider() !== null;
    }

    /**
     * Get active provider name
     */
    public function getActiveProvider(): string
    {
        return Setting::getValue('shipping.default_provider', 'none');
    }

    /**
     * Get flat shipping rate
     */
    public function getFlatRate(): float
    {
        return (float) Setting::getValue('shipping.flat_rate', 29.90);
    }

    /**
     * Get free shipping threshold
     */
    public function getFreeThreshold(): float
    {
        return (float) Setting::getValue('shipping.free_threshold', 500);
    }

    /**
     * Calculate shipping cost for amount
     */
    public function calculateShippingCost(float $orderTotal): float
    {
        $freeThreshold = $this->getFreeThreshold();

        if ($freeThreshold > 0 && $orderTotal >= $freeThreshold) {
            return 0;
        }

        return $this->getFlatRate();
    }

    /**
     * Get amount remaining for free shipping
     */
    public function getRemainingForFreeShipping(float $orderTotal): float
    {
        $freeThreshold = $this->getFreeThreshold();

        if ($freeThreshold <= 0 || $orderTotal >= $freeThreshold) {
            return 0;
        }

        return $freeThreshold - $orderTotal;
    }

    /**
     * Get available providers list
     */
    public function getAvailableProviders(): array
    {
        $available = [];

        foreach ($this->providers as $name => $class) {
            $provider = app($class);
            $available[$name] = [
                'name' => $name,
                'label' => $this->getProviderLabel($name),
                'available' => $provider->isAvailable(),
            ];
        }

        return $available;
    }

    /**
     * Get provider label
     */
    protected function getProviderLabel(string $name): string
    {
        return match ($name) {
            'aras' => 'Aras Kargo',
            'yurtici' => 'Yurtiçi Kargo',
            'mng' => 'MNG Kargo',
            'sendeo' => 'Sendeo',
            'hepsijet' => 'Hepsijet',
            'ptt' => 'PTT Kargo',
            'surat' => 'Sürat Kargo',
            'kolaygelsin' => 'Kolaygelsin',
            'navlungo' => 'Navlungo',
            default => ucfirst($name),
        };
    }
}
