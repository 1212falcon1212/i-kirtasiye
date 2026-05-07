<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    /**
     * Reset Setting in-process cache and store cache before every test.
     *
     * Setting::$resolved is a static array that persists across tests in the
     * same PHP process. RefreshDatabase resets the DB rows but does not
     * touch this static cache; values written by one test would otherwise
     * leak into the next. We force a clean slate here.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->resetSettingsState();
        $this->seedDeterministicCommissionSettings();
    }

    /**
     * Clear both the framework cache and the Setting in-process cache.
     */
    protected function resetSettingsState(): void
    {
        Cache::flush();

        // Setting::$resolved is protected static; flush via reflection.
        $reflection = new \ReflectionClass(Setting::class);
        if ($reflection->hasProperty('resolved')) {
            $prop = $reflection->getProperty('resolved');
            $prop->setAccessible(true);
            $prop->setValue(null, []);
        }
    }

    /**
     * Seed Setting::$resolved with deterministic commission values so tests
     * never depend on whatever the previous test wrote. We populate the
     * in-process cache directly to avoid hitting the DB before migrations
     * run on tests that don't use RefreshDatabase.
     */
    protected function seedDeterministicCommissionSettings(): void
    {
        $reflection = new \ReflectionClass(Setting::class);
        if (! $reflection->hasProperty('resolved')) {
            return;
        }

        $prop = $reflection->getProperty('resolved');
        $prop->setAccessible(true);

        $prop->setValue(null, [
            'commission.enabled' => true,
            'commission.fee_mode' => 'combined',
            'commission.commission_percentage' => 10,
            'commission.flat_service_fee' => 50,
            'commission.withholding_tax_rate' => 1,
            'commission.min_order_amount' => 0,
            'payment.payout_day' => 15,
            'payment.payout_min_amount' => 100,
            'payment.withholding_rate' => 1,
            'shipping.flat_rate' => 0,
            'shipping.free_threshold' => 0,
        ]);
    }
}
