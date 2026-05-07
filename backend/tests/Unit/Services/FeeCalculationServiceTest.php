<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Setting;
use App\Services\FeeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Komisyon, sabit hizmet bedeli ve stopaj hesaplama kurallarını doğrular.
 */
class FeeCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('commission.enabled', true, 'commission', 'boolean');
        Setting::setValue('commission.fee_mode', 'combined', 'commission', 'string');
        Setting::setValue('commission.commission_percentage', '10', 'commission', 'string');
        Setting::setValue('commission.flat_service_fee', '50', 'commission', 'string');
        Setting::setValue('commission.withholding_tax_rate', '1', 'commission', 'string');
        Setting::clearCache();
    }

    /**
     * KDV dahil ₺100 ürün, KDV %20:
     *   netPrice    = 100 / 1.2 = 83.33
     *   commission  = 100 * 0.10 = 10.00
     *   withholding = 83.33 * 0.01 = 0.83
     *   flatFee     = 50 (tek kalemde)
     *   net seller  = 100 - 10 - 50 - 0.83 = 39.17
     */
    public function test_combined_mode_applies_commission_flat_fee_and_withholding(): void
    {
        $service = new FeeCalculationService;

        $fees = $service->calculateFees(
            totalPrice: 100.0,
            flatFeeShare: 50.0,
            shippingCostShare: 0.0,
            categoryCommissionRate: null,
            vatRate: 20.0,
        );

        $this->assertEqualsWithDelta(83.33, $fees['net_price'], 0.01);
        $this->assertEqualsWithDelta(16.67, $fees['vat_amount'], 0.01);
        $this->assertEqualsWithDelta(10.00, $fees['commission_amount'], 0.01);
        $this->assertEqualsWithDelta(50.00, $fees['flat_service_fee'], 0.01);
        $this->assertEqualsWithDelta(60.00, $fees['service_fee_amount'], 0.01);
        $this->assertEqualsWithDelta(0.83, $fees['withholding_tax'], 0.01);
        $this->assertEqualsWithDelta(60.83, $fees['total_fees'], 0.01);
        $this->assertEqualsWithDelta(39.17, $fees['net_seller_amount'], 0.01);
        $this->assertSame(10.0, $fees['commission_rate']);
    }

    /**
     * KDV oranı null geldiğinde config('commission.default_vat_rate') (varsayılan %20) kullanılır.
     * Aynı sayısal sonuç beklenir.
     */
    public function test_falls_back_to_default_vat_rate_when_not_provided(): void
    {
        config()->set('commission.default_vat_rate', 0.20);
        $service = new FeeCalculationService;

        $fees = $service->calculateFees(
            totalPrice: 100.0,
            flatFeeShare: 50.0,
        );

        $this->assertEqualsWithDelta(83.33, $fees['net_price'], 0.01);
        $this->assertEqualsWithDelta(0.83, $fees['withholding_tax'], 0.01);
        $this->assertSame(20.0, $fees['vat_rate']);
    }

    /**
     * fee_mode='flat' iken sadece sabit hizmet bedeli alınır, yüzdelik komisyon sıfırlanır.
     */
    public function test_flat_mode_only_applies_flat_service_fee(): void
    {
        Setting::setValue('commission.fee_mode', 'flat', 'commission', 'string');
        Setting::clearCache();
        $service = new FeeCalculationService;

        $fees = $service->calculateFees(
            totalPrice: 100.0,
            flatFeeShare: 50.0,
            vatRate: 20.0,
        );

        $this->assertSame(0.0, $fees['commission_amount']);
        $this->assertEqualsWithDelta(50.0, $fees['flat_service_fee'], 0.01);
        $this->assertEqualsWithDelta(50.83, $fees['total_fees'], 0.01);
    }

    /**
     * fee_mode='percentage' iken sadece yüzdelik komisyon alınır, sabit ücret sıfırlanır.
     */
    public function test_percentage_mode_only_applies_percentage_commission(): void
    {
        Setting::setValue('commission.fee_mode', 'percentage', 'commission', 'string');
        Setting::clearCache();
        $service = new FeeCalculationService;

        $fees = $service->calculateFees(
            totalPrice: 100.0,
            flatFeeShare: 50.0, // bu modda dikkate alınmamalı
            vatRate: 20.0,
        );

        $this->assertEqualsWithDelta(10.0, $fees['commission_amount'], 0.01);
        $this->assertSame(0.0, $fees['flat_service_fee']);
        $this->assertEqualsWithDelta(10.83, $fees['total_fees'], 0.01);
    }

    /**
     * fee_mode='category' iken kategorinin komisyon oranı kullanılır.
     */
    public function test_category_mode_uses_category_commission_rate(): void
    {
        Setting::setValue('commission.fee_mode', 'category', 'commission', 'string');
        Setting::clearCache();
        $service = new FeeCalculationService;

        $fees = $service->calculateFees(
            totalPrice: 100.0,
            flatFeeShare: 50.0,
            categoryCommissionRate: 8.0,
            vatRate: 20.0,
        );

        $this->assertSame(8.0, $fees['commission_rate']);
        $this->assertEqualsWithDelta(8.0, $fees['commission_amount'], 0.01);
        $this->assertSame(0.0, $fees['flat_service_fee']);
    }

    /**
     * Hizmet bedeli sistemi pasifse komisyon ve sabit hizmet bedeli sıfırlanır,
     * stopaj yine alınır (mali zorunluluk).
     */
    public function test_disabled_service_fee_zeroes_commission_but_keeps_withholding(): void
    {
        Setting::setValue('commission.enabled', false, 'commission', 'boolean');
        Setting::clearCache();
        $service = new FeeCalculationService;

        $fees = $service->calculateFees(
            totalPrice: 100.0,
            flatFeeShare: 50.0,
            vatRate: 20.0,
        );

        $this->assertSame(0.0, $fees['commission_amount']);
        $this->assertSame(0.0, $fees['flat_service_fee']);
        $this->assertEqualsWithDelta(0.83, $fees['withholding_tax'], 0.01);
    }

    /**
     * Stopaj her zaman KDV hariç tutar üzerinden hesaplanır.
     * KDV %0 olan üründe netPrice = totalPrice olur, stopaj = totalPrice * %1.
     */
    public function test_withholding_uses_net_price_when_vat_is_zero(): void
    {
        $service = new FeeCalculationService;

        $fees = $service->calculateFees(
            totalPrice: 100.0,
            flatFeeShare: 50.0,
            vatRate: 0.0,
        );

        $this->assertEqualsWithDelta(100.0, $fees['net_price'], 0.01);
        $this->assertEqualsWithDelta(1.0, $fees['withholding_tax'], 0.01);
    }

    /**
     * getRates default_vat_rate'i config'den çeker ve yüzde formatında döner.
     */
    public function test_get_rates_exposes_default_vat_rate_in_percent(): void
    {
        config()->set('commission.default_vat_rate', 0.20);
        $service = new FeeCalculationService;

        $rates = $service->getRates();

        $this->assertSame('combined', $rates['fee_mode']);
        $this->assertSame(20.0, $rates['default_vat_rate']);
        $this->assertSame(10.0, $rates['commission_percentage']);
        $this->assertSame(50.0, $rates['flat_service_fee']);
        $this->assertSame(1.0, $rates['withholding_tax_rate']);
        $this->assertTrue($rates['service_fee_enabled']);
    }
}
