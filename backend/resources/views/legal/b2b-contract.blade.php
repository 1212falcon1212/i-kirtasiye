<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; color: #333; margin: 20px; }
        h1 { text-align: center; font-size: 16px; margin-bottom: 5px; }
        .subtitle { text-align: center; font-size: 12px; color: #666; margin-bottom: 30px; }
        h2 { font-size: 13px; margin-top: 20px; border-bottom: 1px solid #ddd; padding-bottom: 3px; }
        .info-table { width: 100%; border-collapse: collapse; margin: 8px 0 15px; }
        .info-table td { padding: 4px 8px; border: 1px solid #ddd; font-size: 11px; }
        .info-table td:first-child { font-weight: bold; width: 30%; background: #f5f5f5; }
        .product-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .product-table th { background: #2d5e3a; color: #fff; padding: 6px 8px; font-size: 10px; text-align: left; }
        .product-table td { padding: 5px 8px; border: 1px solid #ddd; font-size: 10px; }
        .product-table tr:nth-child(even) { background: #f9f9f9; }
        .total-row td { font-weight: bold; background: #f0f0f0 !important; }
        .terms { font-size: 10px; line-height: 1.4; }
        .terms p { margin: 4px 0; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <h1>MESAFELİ SATIŞ SÖZLEŞMESİ</h1>
    <div class="subtitle">Sözleşme No: {{ $contract_number }} | Tarih: {{ $date }}</div>

    <h2>1. SATICI BİLGİLERİ</h2>
    <table class="info-table">
        <tr><td>Ticari Ünvan</td><td>{{ $seller_trade_name }}</td></tr>
        <tr><td>MERSİS No</td><td>{{ $seller_mersis }}</td></tr>
        <tr><td>Vergi No / Dairesi</td><td>{{ $seller_tax_number }} / {{ $seller_tax_office }}</td></tr>
        <tr><td>KEP Adresi</td><td>{{ $seller_kep }}</td></tr>
        <tr><td>Adres</td><td>{{ $seller_address }}</td></tr>
        <tr><td>Telefon</td><td>{{ $seller_phone }}</td></tr>
        <tr><td>E-posta</td><td>{{ $seller_email }}</td></tr>
    </table>

    <h2>2. ALICI BİLGİLERİ</h2>
    <table class="info-table">
        <tr><td>Kırtasiye / Şirket Adı</td><td>{{ $buyer_name }}</td></tr>
        <tr><td>Vergi No / Dairesi</td><td>{{ $buyer_tax_number }} / {{ $buyer_tax_office }}</td></tr>
        <tr><td>Adres</td><td>{{ $buyer_address }}</td></tr>
        <tr><td>Telefon</td><td>{{ $buyer_phone }}</td></tr>
        <tr><td>E-posta</td><td>{{ $buyer_email }}</td></tr>
    </table>

    <h2>3. SİPARİŞ DETAYI</h2>
    <table class="product-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Ürün Adı</th>
                <th style="width: 10%; text-align: center;">Adet</th>
                <th style="width: 20%; text-align: right;">Birim Fiyat</th>
                <th style="width: 20%; text-align: right;">Toplam</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item['name'] }}</td>
                <td style="text-align: center;">{{ $item['quantity'] }}</td>
                <td style="text-align: right;">{{ $item['unit_price'] }} TL</td>
                <td style="text-align: right;">{{ $item['total_price'] }} TL</td>
            </tr>
            @endforeach
            @if($shipping_cost > 0)
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold;">Kargo Ücreti</td>
                <td style="text-align: right;">{{ number_format($shipping_cost, 2, ',', '.') }} TL</td>
            </tr>
            @endif
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">GENEL TOPLAM</td>
                <td style="text-align: right;">{{ $grand_total }} TL</td>
            </tr>
        </tbody>
    </table>

    <h2>4. ÖDEME BİLGİLERİ</h2>
    <table class="info-table">
        <tr><td>Ödeme Yöntemi</td><td>{{ $payment_method }}</td></tr>
        <tr><td>Ödeme Durumu</td><td>{{ $payment_status }}</td></tr>
    </table>

    <h2>5. TESLİMAT BİLGİLERİ</h2>
    <table class="info-table">
        <tr><td>Teslimat Adresi</td><td>{{ $delivery_address }}</td></tr>
    </table>

    <h2>6. GENEL HÜKÜMLER</h2>
    <div class="terms">
        @php
            $cmsPage = \App\Models\Page::where('slug', 'mesafeli-satis-sozlesmesi')->published()->first();
        @endphp
        @if($cmsPage && $cmsPage->content)
            {!! $cmsPage->content !!}
        @else
            <p><strong>6.1.</strong> ALICI, sipariş konusu ürünün temel niteliklerini, satış fiyatını ve ödeme şeklini okuyup bilgi sahibi olduğunu kabul eder.</p>
            <p><strong>6.2.</strong> Sipariş konusu ürün, yasal 30 günlük süreyi aşmamak kaydıyla ALICI'ya teslim edilir.</p>
            <p><strong>6.3.</strong> ALICI, ürün tesliminden itibaren 14 gün içinde cayma hakkına sahiptir.</p>
            <p><strong>6.4.</strong> SATICI, sipariş konusu ürünün sağlam, eksiksiz ve siparişe uygun olarak teslim edilmesinden sorumludur.</p>
            <p><strong>6.5.</strong> İşbu sözleşme elektronik ortamda taraflarca onaylanarak yürürlüğe girmiştir.</p>
            <p><strong>6.6.</strong> Uyuşmazlıkların çözümünde İstanbul Mahkemeleri ve İcra Daireleri yetkilidir.</p>
        @endif
    </div>

    <div class="footer">
        <p>Bu sözleşme {{ $date }} tarihinde i-kirtasiye B2B Kırtasiye Pazaryeri üzerinden elektronik ortamda oluşturulmuştur.</p>
        <p>Sipariş No: {{ $order_number }} | Sözleşme No: {{ $contract_number }}</p>
    </div>
</body>
</html>
