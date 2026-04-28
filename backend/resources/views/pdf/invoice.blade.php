<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Fatura - {{ $invoice?->invoice_number ?? $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        .page {
            padding: 30px 40px;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 3px solid #059669;
            padding-bottom: 15px;
        }

        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 4px;
        }

        .company-subtitle {
            font-size: 9px;
            color: #6b7280;
        }

        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 5px;
        }

        .invoice-meta {
            font-size: 9px;
            color: #4b5563;
        }

        .invoice-meta strong {
            color: #1a1a1a;
        }

        /* Parties */
        .parties {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .party {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 12px 15px;
        }

        .party-seller {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
        }

        .party-buyer {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            margin-left: 10px;
        }

        .party-title {
            font-size: 10px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .party-buyer .party-title {
            color: #2563eb;
        }

        .party-detail {
            font-size: 9px;
            margin-bottom: 2px;
            color: #374151;
        }

        .party-detail strong {
            display: inline-block;
            width: 50px;
            color: #1a1a1a;
        }

        .party-name {
            font-size: 11px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        /* Product table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .items-table thead th {
            background-color: #059669;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            padding: 8px 10px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .items-table thead th:last-child,
        .items-table thead th:nth-child(3),
        .items-table thead th:nth-child(4),
        .items-table thead th:nth-child(5) {
            text-align: right;
        }

        .items-table tbody td {
            padding: 7px 10px;
            font-size: 9px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .items-table tbody td:last-child,
        .items-table tbody td:nth-child(3),
        .items-table tbody td:nth-child(4),
        .items-table tbody td:nth-child(5) {
            text-align: right;
        }

        .items-table tbody td:first-child {
            font-weight: 500;
        }

        .product-barcode {
            display: block;
            font-size: 8px;
            color: #9ca3af;
        }

        /* Totals */
        .totals-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .totals-left {
            display: table-cell;
            width: 55%;
            vertical-align: top;
        }

        .totals-right {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px 10px;
            font-size: 9px;
        }

        .totals-table td:last-child {
            text-align: right;
            font-weight: 500;
        }

        .totals-table tr.total-row {
            border-top: 2px solid #059669;
        }

        .totals-table tr.total-row td {
            font-size: 12px;
            font-weight: bold;
            color: #059669;
            padding-top: 8px;
        }

        .totals-table tr.subtotal-row td {
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
        }

        /* Tax breakdown */
        .tax-breakdown {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px 12px;
        }

        .tax-breakdown-title {
            font-size: 9px;
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .tax-breakdown-row {
            display: table;
            width: 100%;
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .tax-breakdown-row span {
            display: table-cell;
        }

        .tax-breakdown-row span:last-child {
            text-align: right;
        }

        /* Payment status */
        .payment-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
        }

        .payment-paid {
            background-color: #dcfce7;
            color: #166534;
        }

        .payment-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .payment-failed {
            background-color: #fecaca;
            color: #991b1b;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 8px;
            color: #6b7280;
            font-style: italic;
        }

        /* Order info */
        .order-info {
            background-color: #fefce8;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 20px;
            font-size: 9px;
        }

        .order-info strong {
            color: #92400e;
        }
    </style>
</head>
<body>
<div class="page">
    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <div class="company-name">i-kirtasiye</div>
            <div class="company-subtitle">B2B Kırtasiye Pazaryeri</div>
            <div class="company-subtitle" style="margin-top: 4px;">www.i-kirtasiye.com</div>
        </div>
        <div class="header-right">
            <div class="invoice-title">FATURA</div>
            <div class="invoice-meta">
                <strong>Fatura No:</strong> {{ $invoice?->invoice_number ?? 'TASLAK' }}<br>
                <strong>Tarih:</strong> {{ $invoice?->created_at?->format('d.m.Y') ?? now()->format('d.m.Y') }}<br>
                <strong>Siparis No:</strong> {{ $order->order_number }}<br>
                <strong>Siparis Tarihi:</strong> {{ $order->created_at->format('d.m.Y H:i') }}
            </div>
        </div>
    </div>

    {{-- Parties --}}
    <div class="parties">
        <div class="party party-seller">
            <div class="party-title">Satici Bilgileri</div>
            @if($seller)
                <div class="party-name">{{ $seller->business_name ?? $seller->trade_name ?? '-' }}</div>
                <div class="party-detail"><strong>Vergi No:</strong> {{ $seller->vergi_no ?? '-' }}</div>
                <div class="party-detail"><strong>Adres:</strong> {{ $seller->address ?? '-' }}, {{ $seller->district ?? '' }} {{ $seller->city ?? '' }}</div>
                <div class="party-detail"><strong>Tel:</strong> {{ $seller->phone ?? '-' }}</div>
                <div class="party-detail"><strong>E-posta:</strong> {{ $seller->email ?? '-' }}</div>
                @if($seller->tax_office || $seller->tax_number)
                    <div class="party-detail"><strong>V.D.:</strong> {{ $seller->tax_office ?? '-' }} / {{ $seller->tax_number ?? '-' }}</div>
                @endif
            @else
                <div class="party-detail">Satici bilgisi bulunamadi</div>
            @endif
        </div>
        <div class="party party-buyer">
            <div class="party-title">Alici Bilgileri</div>
            @if($buyer)
                <div class="party-name">{{ $buyer->business_name ?? $buyer->trade_name ?? '-' }}</div>
                <div class="party-detail"><strong>Vergi No:</strong> {{ $buyer->vergi_no ?? '-' }}</div>
                <div class="party-detail"><strong>Adres:</strong> {{ $buyer->address ?? '-' }}, {{ $buyer->district ?? '' }} {{ $buyer->city ?? '' }}</div>
                <div class="party-detail"><strong>Tel:</strong> {{ $buyer->phone ?? '-' }}</div>
                <div class="party-detail"><strong>E-posta:</strong> {{ $buyer->email ?? '-' }}</div>
                @if($buyer->tax_office || $buyer->tax_number)
                    <div class="party-detail"><strong>V.D.:</strong> {{ $buyer->tax_office ?? '-' }} / {{ $buyer->tax_number ?? '-' }}</div>
                @endif
            @else
                <div class="party-detail">Alici bilgisi bulunamadi</div>
            @endif
        </div>
    </div>

    {{-- Shipping address --}}
    @if($order->shipping_address)
        <div class="order-info">
            <strong>Teslimat Adresi:</strong>
            {{ $order->shipping_address['address'] ?? '' }}
            {{ isset($order->shipping_address['district']) ? ', ' . $order->shipping_address['district'] : '' }}
            {{ isset($order->shipping_address['city']) ? ', ' . $order->shipping_address['city'] : '' }}
            {{ isset($order->shipping_address['postal_code']) ? ' ' . $order->shipping_address['postal_code'] : '' }}
        </div>
    @endif

    {{-- Product table --}}
    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 8%;">#</th>
            <th style="width: 37%;">Urun Adi</th>
            <th style="width: 13%;">Miktar</th>
            <th style="width: 14%;">Birim Fiyat</th>
            <th style="width: 10%;">KDV %</th>
            <th style="width: 18%;">Toplam</th>
        </tr>
        </thead>
        <tbody>
        @foreach($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    {{ $item->product?->name ?? 'Urun' }}
                    @if($item->product?->barcode)
                        <span class="product-barcode">{{ $item->product->barcode }}</span>
                    @endif
                </td>
                <td>{{ $item->quantity }} Adet</td>
                <td>{{ number_format((float) $item->unit_price, 2, ',', '.') }} TL</td>
                <td>%{{ number_format((float) ($item->product?->category?->tax_rate ?? 8), 0) }}</td>
                <td>{{ number_format((float) $item->total_price, 2, ',', '.') }} TL</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals-section">
        <div class="totals-left">
            {{-- Tax breakdown --}}
            @if(count($taxGroups) > 0)
                <div class="tax-breakdown">
                    <div class="tax-breakdown-title">KDV Dokumu</div>
                    @foreach($taxGroups as $rate => $group)
                        <div class="tax-breakdown-row">
                            <span>%{{ number_format($rate, 0) }} KDV (Matrah: {{ number_format($group['base'], 2, ',', '.') }} TL)</span>
                            <span>{{ number_format($group['amount'], 2, ',', '.') }} TL</span>
                        </div>
                    @endforeach
                    <div class="tax-breakdown-row" style="border-top: 1px solid #d1d5db; padding-top: 3px; margin-top: 3px; font-weight: bold;">
                        <span>Toplam KDV</span>
                        <span>{{ number_format($totalTax, 2, ',', '.') }} TL</span>
                    </div>
                </div>
            @endif

            {{-- Payment status --}}
            <div style="margin-top: 12px;">
                <strong style="font-size: 9px;">Odeme Durumu:</strong>
                @php
                    $statusClass = match($order->payment_status) {
                        'paid' => 'payment-paid',
                        'pending' => 'payment-pending',
                        default => 'payment-failed',
                    };
                    $statusLabel = \App\Models\Order::PAYMENT_STATUS_LABELS[$order->payment_status] ?? $order->payment_status;
                @endphp
                <span class="payment-status {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>

            @if($order->payment_method)
                <div style="margin-top: 5px; font-size: 9px; color: #6b7280;">
                    <strong>Odeme Yontemi:</strong>
                    {{ match($order->payment_method) {
                        'credit_card' => 'Kredi Karti',
                        'bank_transfer' => 'Havale/EFT',
                        'wallet' => 'Cuzdan',
                        default => $order->payment_method,
                    } }}
                </div>
            @endif
        </div>
        <div class="totals-right">
            <table class="totals-table">
                <tr>
                    <td>Ara Toplam (KDV Dahil):</td>
                    <td>{{ number_format($subtotal, 2, ',', '.') }} TL</td>
                </tr>
                @if($totalCommission > 0)
                    <tr>
                        <td>Komisyon Kesintisi:</td>
                        <td style="color: #dc2626;">-{{ number_format($totalCommission, 2, ',', '.') }} TL</td>
                    </tr>
                @endif
                @if($totalShipping > 0)
                    <tr>
                        <td>Kargo Ucreti:</td>
                        <td>{{ number_format($totalShipping, 2, ',', '.') }} TL</td>
                    </tr>
                @endif
                @if($order->coupon_discount && (float)$order->coupon_discount > 0)
                    <tr>
                        <td>Kupon Indirimi:</td>
                        <td style="color: #059669;">-{{ number_format((float)$order->coupon_discount, 2, ',', '.') }} TL</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>GENEL TOPLAM:</td>
                    <td>{{ number_format((float) $order->total_amount, 2, ',', '.') }} TL</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- Notes --}}
    @if($order->notes)
        <div style="margin-top: 10px; padding: 8px 12px; background-color: #f9fafb; border-radius: 4px; font-size: 9px;">
            <strong>Siparis Notu:</strong> {{ $order->notes }}
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div>Bu belge i-kirtasiye B2B Kırtasiye Pazaryeri uzerinden otomatik olarak olusturulmustur.</div>
        <div style="margin-top: 3px;">Olusturulma Tarihi: {{ now()->format('d.m.Y H:i') }}</div>
        <div class="footer-note">
            Bu belge bilgilendirme amaclidir. Resmi fatura niteliginde degildir.
            E-fatura icin satici ile iletisime geciniz.
        </div>
    </div>
</div>
</body>
</html>
