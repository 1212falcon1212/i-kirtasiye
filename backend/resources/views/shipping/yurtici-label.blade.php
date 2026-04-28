@php
    /** @var \App\Models\Order $order */
    $barcode = new \Milon\Barcode\DNS1D();
    $orderBarcode = $barcode->getBarcodePNG((string) $order->order_number, 'C128', 2, 60);
    $projectBarcode = $projectCode && $projectCode !== '—'
        ? $barcode->getBarcodePNG((string) $projectCode, 'C128', 1.5, 30)
        : null;
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yurtiçi Kargo - {{ $order->order_number }}</title>
    <style>
        @page { margin: 4mm; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9pt;
            color: #111;
            margin: 0;
            padding: 0;
        }
        .label { width: 92mm; }
        .header {
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        .header .platform {
            text-align: center;
            font-weight: 700;
            font-size: 10pt;
        }
        .header .yk {
            text-align: right;
            font-weight: 700;
            color: #d70026;
            font-size: 11pt;
            letter-spacing: 0.5px;
        }
        .header .brand {
            font-weight: 700;
            font-size: 9pt;
            color: #0390b1;
        }
        .project {
            text-align: center;
            margin-bottom: 4px;
        }
        .project img { display: block; margin: 0 auto; }
        .project .label-text {
            font-size: 8pt;
            margin-top: 2px;
        }
        .ref {
            text-align: center;
            margin: 6px 0;
            padding-bottom: 4px;
            border-bottom: 1px solid #000;
        }
        .ref img { display: block; margin: 0 auto; }
        .ref .label-text {
            font-size: 9pt;
            margin-top: 3px;
            font-weight: 700;
        }
        .box {
            border: 1px solid #000;
            padding: 4px 6px;
            margin-bottom: 5px;
        }
        .box .title {
            font-weight: 700;
            font-size: 8pt;
            margin-bottom: 3px;
            border-bottom: 1px dashed #999;
            padding-bottom: 1px;
        }
        .field { margin: 1px 0; line-height: 1.3; }
        .field strong { display: inline-block; min-width: 36px; }
        .footer {
            font-size: 7pt;
            color: #555;
            text-align: center;
            margin-top: 6px;
        }
    </style>
</head>
<body>
<div class="label">
    <div class="header">
        <table>
            <tr>
                <td style="width: 35%;"><span class="brand">i-kirtasiye</span></td>
                <td class="platform">Yeni Öder Platformu</td>
                <td style="width: 25%;" class="yk">yurtiçikargo</td>
            </tr>
        </table>
    </div>

    @if($projectBarcode)
        <div class="project">
            <img src="data:image/png;base64,{{ $projectBarcode }}" alt="Proje Kodu" style="max-width:60mm; height:30px;">
            <div class="label-text">Proje Kodu: {{ $projectCode }}</div>
        </div>
    @endif

    <div class="ref">
        <img src="data:image/png;base64,{{ $orderBarcode }}" alt="Referans Kodu" style="max-width:88mm; height:55px;">
        <div class="label-text">Referans Kodu: {{ $order->order_number }}</div>
    </div>

    <div class="box">
        <div class="title">Alıcı Bilgileri</div>
        <div class="field"><strong>Ad Soyad:</strong> {{ $consignee['name'] ?: '-' }}</div>
        <div class="field"><strong>Adres:</strong> {{ $consignee['address'] ?: '-' }}</div>
        <div class="field"><strong>Telefon:</strong> {{ $consignee['phone'] ?: '-' }}</div>
    </div>

    <div class="box">
        <div class="title">Gönderici Bilgileri</div>
        <div class="field"><strong>Ad Soyad:</strong> {{ $sender['name'] ?: '-' }}</div>
        <div class="field"><strong>Adres:</strong> {{ $sender['address'] ?: '-' }}</div>
        <div class="field"><strong>Telefon:</strong> {{ $sender['phone'] ?: '-' }}</div>
    </div>

    <div class="footer">
        i-kirtasiye.com · Sipariş #{{ $order->order_number }} · {{ now()->format('d.m.Y H:i') }}
    </div>
</div>
</body>
</html>
