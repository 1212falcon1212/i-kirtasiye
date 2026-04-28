<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Shipping\ArasProvider;
use Illuminate\Console\Command;

class TestArasIntegration extends Command
{
    protected $signature = 'aras:test
        {--scenario=all : single, multi, cod, cod-multi, all}
        {--no-cancel : Test sonrası CancelDispatch çağrısını atla}';

    protected $description = 'Aras Kargo entegrasyon test senaryolarını çalıştırır (döküman: Sevkiyat Test Senaryoları)';

    public function handle(ArasProvider $provider): int
    {
        if (! $provider->isAvailable()) {
            $this->error('Aras Kargo entegrasyonu aktif değil. Admin → Kargo Ayarları üzerinden aktif edin.');

            return self::FAILURE;
        }

        $scenario = $this->option('scenario');
        $scenarios = match ($scenario) {
            'single' => ['single'],
            'multi' => ['multi'],
            'cod' => ['cod'],
            'cod-multi' => ['cod-multi'],
            'all' => ['single', 'multi', 'cod', 'cod-multi'],
            default => ['single'],
        };

        $this->info('─── Aras Kargo Test Senaryoları ───');
        $this->newLine();

        $results = [];
        foreach ($scenarios as $s) {
            $results[] = $this->runScenario($s, $provider);
        }

        $this->newLine();
        $this->table(
            ['Senaryo', 'IntegrationCode', 'Sonuç', 'Mesaj'],
            array_map(
                fn ($r) => [$r['scenario'], $r['code'], $r['success'] ? 'BAŞARILI' : 'HATA', $r['message']],
                $results
            )
        );

        $successful = array_filter($results, fn ($r) => $r['success']);
        if (count($successful) > 0) {
            $this->newLine();
            $this->info('Aras BT ekibine gönderilecek IntegrationCode\'lar:');
            foreach ($successful as $r) {
                $this->line('  • '.$r['code'].' ('.$r['scenario'].')');
            }
        }

        return count($successful) === count($results) ? self::SUCCESS : self::FAILURE;
    }

    protected function runScenario(string $scenario, ArasProvider $provider): array
    {
        $order = $this->buildTestOrder($scenario);
        $senderInfo = $this->buildSenderInfo($scenario);

        $this->line(sprintf('Senaryo [%s] çalışıyor...', $scenario));

        $result = $provider->createShipment($order, $senderInfo);
        $integrationCode = $provider->generateIntegrationCode($order);

        if (! $result->success) {
            return [
                'scenario' => $scenario,
                'code' => $integrationCode,
                'success' => false,
                'message' => $result->error ?? 'Bilinmeyen hata',
            ];
        }

        $verify = $provider->getOrderWithIntegrationCode($integrationCode);
        $verifyMessage = ! empty($verify) ? 'SetOrder + doğrulama OK' : 'SetOrder OK (doğrulama boş)';

        if (! $this->option('no-cancel')) {
            $order->setAttribute('tracking_number', $integrationCode);
            $cancel = $provider->cancelShipment($order);
            if ($cancel->success) {
                $verifyMessage .= ' + iptal edildi';
            }
        }

        return [
            'scenario' => $scenario,
            'code' => $integrationCode,
            'success' => true,
            'message' => $verifyMessage,
        ];
    }

    protected function buildTestOrder(string $scenario): Order
    {
        $orderNumber = 'TEST'.now()->format('ymdHis').random_int(100, 999);
        $id = random_int(100000, 999999);

        $order = new Order([
            'order_number' => $orderNumber,
            'user_id' => 0,
            'subtotal' => 100.00,
            'total_amount' => str_contains($scenario, 'cod') ? 250.00 : 100.00,
            'shipping_cost' => 29.90,
            'payment_method' => str_contains($scenario, 'cod') ? 'cod' : 'card',
            'payment_status' => 'paid',
            'shipping_status' => 'pending',
            'status' => 'confirmed',
            'shipping_address' => [
                'name' => 'Test Alıcı',
                'address' => 'Bağlarbaşı Mah. Aydın Sok. No:7',
                'city' => 'İstanbul',
                'district' => 'Gaziosmanpaşa',
                'phone' => '05063390181',
            ],
        ]);
        $order->setAttribute('id', $id);
        $order->setAttribute('created_at', now());

        return $order;
    }

    protected function buildSenderInfo(string $scenario): array
    {
        $base = [
            'name' => 'i-kirtasiye Test',
            'address' => 'Rüzgarlıbahçe Mah. No:1',
            'city' => 'İstanbul',
            'district' => 'Beykoz',
            'phone' => '02165385562',
            'piece_count' => str_contains($scenario, 'multi') ? 3 : 1,
        ];

        if (str_contains($scenario, 'cod')) {
            $base['is_cod'] = true;
            $base['cod_amount'] = 250.00;
            $base['cod_collection_type'] = '0';
        }

        return $base;
    }
}
