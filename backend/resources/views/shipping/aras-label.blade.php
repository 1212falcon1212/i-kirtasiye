@php
    /** @var list<array{index:int,total:int,barcode:string}> $pieces */
    $barcodeGenerator = new \Picqer\Barcode\BarcodeGeneratorSVG();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Aras Kargo Etiketi - {{ $integrationCode }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; }

        .label {
            width: 100mm;
            min-height: 150mm;
            border: 2px solid #000;
            padding: 4mm;
            margin: 5mm auto;
            page-break-after: always;
        }
        .label:last-of-type { page-break-after: auto; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }

        .logo-text {
            font-size: 22px;
            font-weight: bold;
            color: #e30613;
            letter-spacing: 1px;
        }

        .logo-sub {
            font-size: 8px;
            color: #666;
        }

        .piece-info {
            text-align: right;
            font-size: 11px;
            font-weight: bold;
        }

        .cod-banner {
            background: #e30613;
            color: white;
            text-align: center;
            padding: 3mm;
            margin-bottom: 3mm;
            font-weight: bold;
        }
        .cod-banner .cod-title { font-size: 14px; letter-spacing: 2px; }
        .cod-banner .cod-amount { font-size: 18px; margin-top: 1mm; }
        .cod-banner .cod-type { font-size: 10px; margin-top: 1mm; }

        .tracking-section {
            text-align: center;
            padding: 4mm 0;
            border-bottom: 2px solid #000;
            margin-bottom: 3mm;
        }

        .tracking-number {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-bottom: 2mm;
        }

        .barcode {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 55px;
            margin: 3mm 0;
            background: white;
        }
        .barcode svg { max-width: 85mm; height: 50px; }

        .section {
            border-bottom: 1px solid #ccc;
            padding: 2mm 0;
            margin-bottom: 2mm;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1mm;
        }

        .section-content { font-size: 12px; line-height: 1.4; }
        .section-content .name { font-weight: bold; font-size: 13px; margin-bottom: 1mm; }

        .info-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 2mm;
            border-bottom: 1px solid #ccc;
            padding: 2mm 0;
            margin-bottom: 2mm;
        }
        .info-item { flex: 1 1 45%; }
        .info-label { font-size: 8px; color: #888; text-transform: uppercase; }
        .info-value { font-size: 11px; font-weight: bold; }

        .footer {
            text-align: center;
            font-size: 8px;
            color: #999;
            padding-top: 2mm;
        }

        .mok-code { font-size: 10px; color: #333; text-align: center; margin-top: 1mm; }

        @media print {
            body { margin: 0; }
            .label { border: 2px solid #000; margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; padding:10px; background:#f5f5f5;">
        <button onclick="window.print()" style="padding:8px 24px; font-size:14px; cursor:pointer; background:#e30613; color:white; border:none; border-radius:4px;">
            Yazdır ({{ count($pieces) }} etiket)
        </button>
    </div>

    @foreach ($pieces as $piece)
        <div class="label">
            {{-- Header --}}
            <div class="header">
                <div>
                    <div class="logo-text">aras kargo</div>
                    <div class="logo-sub">i-kirtasiye.com B2B Kırtasiye Pazaryeri</div>
                </div>
                <div class="piece-info">
                    <div>{{ $orderDate }}</div>
                    <div>Parça: <strong>{{ $piece['index'] }}/{{ $piece['total'] }}</strong></div>
                </div>
            </div>

            {{-- COD Banner (tahsilatlı gönderiler için) --}}
            @if ($isCod)
                <div class="cod-banner">
                    <div class="cod-title">TAHSİLATLI KARGO</div>
                    <div class="cod-amount">{{ number_format($codAmount, 2, ',', '.') }} ₺</div>
                    <div class="cod-type">{{ $codCollectionType === '1' ? 'KREDİ KARTI' : 'NAKİT' }}</div>
                </div>
            @endif

            {{-- Barkod (Parça barkod numarası - Code128) --}}
            <div class="tracking-section">
                <div class="tracking-number">{{ $piece['barcode'] }}</div>
                <div class="barcode">
                    {!! $barcodeGenerator->getBarcode($piece['barcode'], \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128, 2, 50) !!}
                </div>
                <div class="mok-code">MÖK: {{ $integrationCode }}</div>
            </div>

            {{-- Gönderici --}}
            <div class="section">
                <div class="section-title">Gönderici</div>
                <div class="section-content">
                    <div class="name">{{ $senderName }}</div>
                    <div>{{ $senderAddress }}</div>
                    <div>{{ $senderDistrict }} / {{ $senderCity }}</div>
                    <div>Tel: {{ $senderPhone }}</div>
                </div>
            </div>

            {{-- Alıcı --}}
            <div class="section">
                <div class="section-title">Alıcı</div>
                <div class="section-content">
                    <div class="name">{{ $receiverName }}</div>
                    <div>{{ $receiverAddress }}</div>
                    <div>{{ $receiverDistrict }} / {{ $receiverCity }}</div>
                    <div>Tel: {{ $receiverPhone }}</div>
                </div>
            </div>

            {{-- Kargo Bilgileri --}}
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Toplam Parça</div>
                    <div class="info-value">{{ $pieceCount }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Desi</div>
                    <div class="info-value">{{ $desi }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Ağırlık</div>
                    <div class="info-value">{{ $weight }} kg</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Ödeme</div>
                    <div class="info-value">{{ $isCod ? 'TAHSİLATLI' : 'GÖNDERİCİ ÖDEMELİ' }}</div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="footer">
                Aras Kargo A.Ş. &bull; 444 25 52 &bull; araskargo.com.tr
            </div>
        </div>
    @endforeach
</body>
</html>
