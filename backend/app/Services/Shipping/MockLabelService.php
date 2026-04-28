<?php

namespace App\Services\Shipping;

use App\Models\Order;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class MockLabelService
{
    /**
     * Generate a mock shipping label for test mode
     */
    public function generateLabel(Order $order, array $senderInfo = []): array
    {
        // Generate mock tracking number
        $trackingNumber = 'TEST-' . strtoupper(Str::random(10));

        // Generate label PDF
        $labelPath = $this->createLabelPdf($order, $trackingNumber, $senderInfo);

        return [
            'success' => true,
            'tracking_number' => $trackingNumber,
            'label_url' => $labelPath,
            'message' => 'Test modu: Kargo etiketi başarıyla oluşturuldu.',
        ];
    }

    /**
     * Create label PDF
     */
    protected function createLabelPdf(Order $order, string $trackingNumber, array $senderInfo): string
    {
        $shippingAddress = $order->shipping_address;
        if (is_string($shippingAddress)) {
            $shippingAddress = json_decode($shippingAddress, true) ?? [];
        } elseif (!is_array($shippingAddress)) {
            $shippingAddress = [];
        }

        $data = [
            'order' => $order,
            'tracking_number' => $trackingNumber,
            'barcode' => $this->generateBarcodeData($trackingNumber),
            'sender' => [
                'name' => $senderInfo['name'] ?? 'Demo Kırtasiye',
                'address' => $senderInfo['address'] ?? 'Test Adres',
                'city' => $senderInfo['city'] ?? 'İstanbul',
                'phone' => $senderInfo['phone'] ?? '0532 000 0000',
            ],
            'receiver' => [
                'name' => $shippingAddress['name'] ?? $order->user->business_name ?? 'Alıcı',
                'address' => $shippingAddress['address'] ?? 'Adres bilgisi yok',
                'city' => $shippingAddress['city'] ?? 'İstanbul',
                'district' => $shippingAddress['district'] ?? '',
                'phone' => $shippingAddress['phone'] ?? '',
            ],
            'date' => Carbon::now()->format('d.m.Y H:i'),
        ];

        // Create simple HTML label
        $html = $this->generateLabelHtml($data);

        // Save as HTML file (simulating PDF for demo)
        $filename = 'label-' . $order->id . '-' . time() . '.html';
        $path = 'labels/' . $filename;

        // Ensure directory exists
        $fullPath = storage_path('app/public/' . $path);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $html);

        return '/storage/' . $path;
    }

    /**
     * Generate label HTML
     */
    protected function generateLabelHtml(array $data): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Kargo Etiketi - {$data['tracking_number']}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; }
        .label { width: 400px; border: 2px solid #000; padding: 15px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { font-size: 16px; margin-bottom: 5px; }
        .barcode { text-align: center; margin: 15px 0; padding: 10px; background: #f0f0f0; }
        .barcode-text { font-family: 'Courier New', monospace; font-size: 24px; font-weight: bold; letter-spacing: 3px; }
        .tracking { text-align: center; font-size: 14px; margin-top: 5px; }
        .section { margin-bottom: 15px; }
        .section-title { font-size: 12px; font-weight: bold; color: #666; margin-bottom: 5px; }
        .address { font-size: 14px; line-height: 1.5; }
        .address .name { font-weight: bold; font-size: 16px; }
        .divider { border-top: 1px dashed #000; margin: 15px 0; }
        .footer { font-size: 10px; text-align: center; color: #666; }
        .order-info { display: flex; justify-content: space-between; font-size: 12px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="label">
        <div class="header">
            <h1>🏥 i-kirtasiye Kargo</h1>
            <small>Test Modu - Demo Etiket</small>
        </div>
        
        <div class="barcode">
            <div class="barcode-text">{$data['tracking_number']}</div>
            <div class="tracking">Takip No: {$data['tracking_number']}</div>
        </div>
        
        <div class="section">
            <div class="section-title">GÖNDEREN</div>
            <div class="address">
                <div class="name">{$data['sender']['name']}</div>
                <div>{$data['sender']['address']}</div>
                <div>{$data['sender']['city']}</div>
                <div>Tel: {$data['sender']['phone']}</div>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <div class="section">
            <div class="section-title">ALICI</div>
            <div class="address">
                <div class="name">{$data['receiver']['name']}</div>
                <div>{$data['receiver']['address']}</div>
                <div>{$data['receiver']['district']} / {$data['receiver']['city']}</div>
                <div>Tel: {$data['receiver']['phone']}</div>
            </div>
        </div>
        
        <div class="order-info">
            <span>Sipariş: {$data['order']->order_number}</span>
            <span>Tarih: {$data['date']}</span>
        </div>
        
        <div class="footer">
            <br>Bu etiket test amaçlı oluşturulmuştur.
        </div>
    </div>
    
    <script>
        // Auto-print when opened
        window.onload = function() {
            // window.print();
        };
    </script>
</body>
</html>
HTML;
    }

    /**
     * Generate barcode data (simplified)
     */
    protected function generateBarcodeData(string $trackingNumber): string
    {
        // Return tracking number for display (actual barcode generation would use a library)
        return $trackingNumber;
    }

    /**
     * Get mock tracking info
     */
    public function trackShipment(string $trackingNumber): array
    {
        $now = Carbon::now();

        return [
            'success' => true,
            'tracking_number' => $trackingNumber,
            'status' => 'in_transit',
            'status_label' => 'Test: Yolda',
            'history' => [
                [
                    'date' => $now->copy()->subHours(2)->format('d.m.Y H:i'),
                    'status' => 'Dağıtım merkezinden çıktı',
                    'location' => 'İstanbul Transfer Merkezi',
                ],
                [
                    'date' => $now->copy()->subHours(12)->format('d.m.Y H:i'),
                    'status' => 'Transfer merkezine ulaştı',
                    'location' => 'İstanbul Transfer Merkezi',
                ],
                [
                    'date' => $now->copy()->subDay()->format('d.m.Y H:i'),
                    'status' => 'Kargoya verildi',
                    'location' => 'Gönderici Şubesi',
                ],
            ],
        ];
    }
}
