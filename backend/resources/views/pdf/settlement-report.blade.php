<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hakedis Raporu - {{ $seller->business_name ?? 'Satici' }}</title>
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

        .report-title {
            font-size: 20px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 5px;
        }

        .report-meta {
            font-size: 9px;
            color: #4b5563;
        }

        .report-meta strong {
            color: #1a1a1a;
        }

        /* Seller info */
        .seller-info {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 4px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        .seller-info-title {
            font-size: 10px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .seller-info-name {
            font-size: 12px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        .seller-info-detail {
            font-size: 9px;
            color: #374151;
            margin-bottom: 2px;
        }

        .seller-info-detail strong {
            display: inline-block;
            width: 55px;
            color: #1a1a1a;
        }

        /* Period badge */
        .period-badge {
            display: inline-block;
            background-color: #059669;
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .type-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            margin-left: 8px;
        }

        .type-upcoming {
            background-color: #fef3c7;
            color: #92400e;
        }

        .type-past {
            background-color: #dcfce7;
            color: #166534;
        }

        /* Summary table */
        .summary-section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #059669;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 7px 12px;
            font-size: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .summary-table td:last-child {
            text-align: right;
            font-weight: 600;
            width: 30%;
        }

        .summary-table tr.credit td:last-child {
            color: #059669;
        }

        .summary-table tr.debit td:last-child {
            color: #dc2626;
        }

        .summary-table tr.total-row {
            border-top: 2px solid #059669;
            background-color: #f0fdf4;
        }

        .summary-table tr.total-row td {
            font-size: 12px;
            font-weight: bold;
            color: #059669;
            padding: 10px 12px;
            border-bottom: none;
        }

        /* Orders table */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .orders-table thead th {
            background-color: #059669;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            padding: 7px 8px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .orders-table thead th:nth-child(n+3) {
            text-align: right;
        }

        .orders-table tbody td {
            padding: 6px 8px;
            font-size: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .orders-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .orders-table tbody td:nth-child(n+3) {
            text-align: right;
        }

        .orders-table tfoot td {
            padding: 8px 8px;
            font-size: 9px;
            font-weight: bold;
            border-top: 2px solid #059669;
            background-color: #f0fdf4;
        }

        .orders-table tfoot td:nth-child(n+3) {
            text-align: right;
        }

        /* Order items detail */
        .order-items {
            margin-left: 20px;
            margin-bottom: 5px;
        }

        .order-items-row {
            font-size: 7px;
            color: #6b7280;
            padding: 1px 0;
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

        /* Amount formatting helper */
        .amount-positive {
            color: #059669;
        }

        .amount-negative {
            color: #dc2626;
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
            <div class="report-title">HAKEDIS RAPORU</div>
            <div class="report-meta">
                <strong>Rapor Tarihi:</strong> {{ $generatedAt }}<br>
                <strong>Donem:</strong> {{ \Carbon\Carbon::parse($date)->locale('tr')->translatedFormat('d F Y') }}
            </div>
        </div>
    </div>

    {{-- Period & Type --}}
    <div style="margin-bottom: 15px;">
        <span class="period-badge">
            {{ \Carbon\Carbon::parse($date)->locale('tr')->translatedFormat('d F Y') }}
        </span>
        <span class="type-badge {{ $type === 'upcoming' ? 'type-upcoming' : 'type-past' }}">
            {{ $type === 'upcoming' ? 'Bekleyen Hakedis' : 'Odenmis Hakedis' }}
        </span>
    </div>

    {{-- Seller info --}}
    <div class="seller-info">
        <div class="seller-info-title">Satici Bilgileri</div>
        <div class="seller-info-name">{{ $seller->business_name ?? $seller->trade_name ?? '-' }}</div>
        <div class="seller-info-detail"><strong>Vergi No:</strong> {{ $seller->vergi_no ?? '-' }}</div>
        <div class="seller-info-detail"><strong>Adres:</strong> {{ $seller->address ?? '-' }}, {{ $seller->district ?? '' }} {{ $seller->city ?? '' }}</div>
        <div class="seller-info-detail"><strong>Tel:</strong> {{ $seller->phone ?? '-' }}</div>
        <div class="seller-info-detail"><strong>E-posta:</strong> {{ $seller->email ?? '-' }}</div>
        @if($seller->tax_office || $seller->tax_number)
            <div class="seller-info-detail"><strong>V.D.:</strong> {{ $seller->tax_office ?? '-' }} / {{ $seller->tax_number ?? '-' }}</div>
        @endif
    </div>

    {{-- Summary Section --}}
    @if(!empty($summary))
        <div class="summary-section">
            <div class="section-title">Hakedis Ozeti</div>
            <table class="summary-table">
                @foreach($summary as $row)
                    <tr class="{{ $row['type'] === 'total' ? 'total-row' : ($row['type'] === 'credit' ? 'credit' : 'debit') }}">
                        <td>
                            {{ $row['label'] }}
                            @if(!empty($row['description']))
                                <br><span style="font-size: 8px; color: #9ca3af;">{{ $row['description'] }}</span>
                            @endif
                        </td>
                        <td>
                            @if($row['type'] === 'debit')
                                {{ number_format(abs($row['amount']), 2, ',', '.') }} TL
                            @else
                                {{ number_format($row['amount'], 2, ',', '.') }} TL
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    {{-- Orders detail table --}}
    @if(!empty($details))
        <div class="summary-section">
            <div class="section-title">Siparis Detaylari ({{ count($details) }} Siparis)</div>
            <table class="orders-table">
                <thead>
                <tr>
                    <th style="width: 14%;">Siparis No</th>
                    <th style="width: 10%;">Tarih</th>
                    <th style="width: 8%;">Adet</th>
                    <th style="width: 14%;">Brut Tutar</th>
                    <th style="width: 14%;">Komisyon</th>
                    <th style="width: 12%;">Stopaj</th>
                    <th style="width: 12%;">Kargo</th>
                    <th style="width: 16%;">Net Tutar</th>
                </tr>
                </thead>
                <tbody>
                @php
                    $totalGross = 0;
                    $totalServiceFee = 0;
                    $totalWithholding = 0;
                    $totalShippingShare = 0;
                    $totalNet = 0;
                    $totalItemCount = 0;
                @endphp
                @foreach($details as $detail)
                    @php
                        $totalGross += $detail['total_price'];
                        $totalServiceFee += $detail['service_fee'];
                        $totalWithholding += $detail['withholding_tax'];
                        $totalShippingShare += $detail['shipping_share'];
                        $totalNet += $detail['net_amount'];
                        $totalItemCount += $detail['item_count'];
                    @endphp
                    <tr>
                        <td>{{ $detail['order_number'] }}</td>
                        <td>{{ $detail['order_date'] }}</td>
                        <td style="text-align: center;">{{ $detail['item_count'] }}</td>
                        <td>{{ number_format($detail['total_price'], 2, ',', '.') }} TL</td>
                        <td class="amount-negative">-{{ number_format($detail['service_fee'], 2, ',', '.') }} TL</td>
                        <td class="amount-negative">-{{ number_format($detail['withholding_tax'], 2, ',', '.') }} TL</td>
                        <td class="amount-negative">-{{ number_format($detail['shipping_share'], 2, ',', '.') }} TL</td>
                        <td style="font-weight: bold;">{{ number_format($detail['net_amount'], 2, ',', '.') }} TL</td>
                    </tr>
                    {{-- Per-order item details --}}
                    @if(!empty($detail['items']))
                        <tr>
                            <td colspan="8" style="padding: 2px 8px 8px 8px; border-bottom: 1px solid #d1d5db;">
                                <div class="order-items">
                                    @foreach($detail['items'] as $item)
                                        <div class="order-items-row">
                                            - {{ $item['product_name'] }} ({{ $item['quantity'] }} x {{ number_format($item['unit_price'], 2, ',', '.') }} TL = {{ number_format($item['total_price'], 2, ',', '.') }} TL)
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
                <tfoot>
                <tr>
                    <td>TOPLAM</td>
                    <td></td>
                    <td style="text-align: center;">{{ $totalItemCount }}</td>
                    <td style="text-align: right;">{{ number_format($totalGross, 2, ',', '.') }} TL</td>
                    <td style="text-align: right; color: #dc2626;">-{{ number_format($totalServiceFee, 2, ',', '.') }} TL</td>
                    <td style="text-align: right; color: #dc2626;">-{{ number_format($totalWithholding, 2, ',', '.') }} TL</td>
                    <td style="text-align: right; color: #dc2626;">-{{ number_format($totalShippingShare, 2, ',', '.') }} TL</td>
                    <td style="text-align: right; color: #059669; font-size: 10px;">{{ number_format($totalNet, 2, ',', '.') }} TL</td>
                </tr>
                </tfoot>
            </table>
        </div>
    @else
        <div style="text-align: center; padding: 30px; color: #9ca3af; font-size: 11px;">
            Bu donem icin siparis detayi bulunamadi.
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div>Bu rapor i-kirtasiye B2B Kırtasiye Pazaryeri uzerinden otomatik olarak olusturulmustur.</div>
        <div style="margin-top: 3px;">Olusturulma Tarihi: {{ $generatedAt }}</div>
        <div class="footer-note">
            Bu belge bilgilendirme amaclidir. Resmi hakedis belgesi niteliginde degildir.
            Detayli bilgi icin platform yonetimiyle iletisime geciniz.
        </div>
    </div>
</div>
</body>
</html>
