<!DOCTYPE html>
<html lang="tr" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Yeni Sipariş — i-kirtasiye</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f0fdfa; color: #1a1a1a; }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#f0fdfa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <div style="display:none;font-size:1px;color:#f0fdfa;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        Yeni bir sipariş aldınız! Sipariş #{{ $order->order_number }} - {{ $subOrder->items->count() }} ürün hazırlanmayı bekliyor.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f0fdfa;">
        <tr>
            <td align="center" style="padding:40px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;">

                    <!-- Header -->
                    <tr>
                        <td align="center" style="background:linear-gradient(135deg,#0390b1,#027a96);padding:40px 40px 32px;border-radius:16px 16px 0 0;">
                            <h1 style="margin:0 0 6px;font-size:32px;font-weight:800;color:#ffffff;letter-spacing:0.5px;">i-kirtasiye</h1>
                            <p style="margin:0;font-size:14px;color:rgba(255,255,255,0.85);font-weight:400;">Yeni bir siparişiniz var!</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background:#ffffff;padding:40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <!-- Bell Icon -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-bottom:24px;">
                                        <div style="width:64px;height:64px;border-radius:50%;background:#ecfeff;display:inline-block;line-height:64px;text-align:center;font-size:28px;">
                                            &#128276;
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#1a1a1a;">
                                Yeni Sipariş Bildirimi
                            </h2>

                            <p style="margin:0 0 28px;font-size:15px;line-height:1.7;color:#6b7280;">
                                Platformda yeni bir sipariş aldınız. Lütfen en kısa sürede siparişi hazırlayınız.
                            </p>

                            <!-- Order Summary Box -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
                                <tr>
                                    <td style="background:#ecfeff;border:1px solid #67e8f9;border-radius:12px;padding:20px 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="padding:6px 0;font-size:14px;color:#6b7280;">Sipariş No:</td>
                                                <td style="padding:6px 0;font-size:14px;font-weight:700;color:#1a1a1a;text-align:right;">#{{ $order->order_number }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;font-size:14px;color:#6b7280;">Sipariş Tarihi:</td>
                                                <td style="padding:6px 0;font-size:14px;font-weight:600;color:#1a1a1a;text-align:right;">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;font-size:14px;color:#6b7280;">Ürün Sayısı:</td>
                                                <td style="padding:6px 0;font-size:14px;font-weight:600;color:#1a1a1a;text-align:right;">{{ $subOrder->items->count() }} ürün</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="border-top:1px solid #a5f3fc;padding-top:10px;"></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;font-size:16px;font-weight:700;color:#1a1a1a;">Toplam:</td>
                                                <td style="padding:6px 0;font-size:18px;font-weight:800;color:#0390b1;text-align:right;">{{ number_format($subOrder->subtotal, 2, ',', '.') }} &#8378;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Product Items -->
                            @if($subOrder->items && $subOrder->items->count() > 0)
                            <h3 style="margin:0 0 16px;font-size:16px;font-weight:700;color:#1a1a1a;">Sipariş Kalemleri</h3>
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                                @foreach($subOrder->items as $item)
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="width:60px;padding:12px;">
                                        @if($item->product && ($item->product->image_url || $item->product->image))
                                            <img src="{{ $item->product->image_url ?? $item->product->image }}" width="50" height="50" alt="{{ $item->product->name ?? 'Ürün' }}" style="border-radius:8px;object-fit:cover;border:1px solid #e5e7eb;display:block;">
                                        @else
                                            <div style="width:50px;height:50px;background:#f3f4f6;border-radius:8px;text-align:center;line-height:50px;font-size:22px;">&#128230;</div>
                                        @endif
                                    </td>
                                    <td style="padding:12px;">
                                        <div style="font-weight:600;color:#1a1a1a;font-size:14px;">{{ $item->product->name ?? 'Ürün' }}</div>
                                        <div style="color:#6b7280;font-size:12px;margin-top:2px;">{{ $item->quantity }} adet</div>
                                    </td>
                                    <td style="padding:12px;text-align:right;font-weight:700;color:#1a1a1a;font-size:14px;white-space:nowrap;">
                                        {{ number_format($item->price * $item->quantity, 2, ',', '.') }} &#8378;
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            <!-- CTA Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding:8px 0 24px;">
                                        <a href="https://i-kirtasiye.com/market/hesabim?tab=siparislerim" target="_blank" style="display:inline-block;background:#0390b1;color:#ffffff;padding:16px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:700;letter-spacing:0.3px;">
                                            Siparişleri Görüntüle
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;line-height:1.6;color:#9ca3af;text-align:center;">
                                Sipariş detaylarını görüntülemek ve işlem yapmak için yukarıdaki butona tıklayınız.
                            </p>

                            <!-- Divider -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr><td style="border-top:1px solid #e5e7eb;padding-top:24px;margin-top:24px;"></td></tr>
                            </table>

                            <p style="margin:0;font-size:15px;line-height:1.7;color:#6b7280;">
                                Saygılarımızla,<br>
                                <strong style="color:#1a1a1a;">i-kirtasiye Ekibi</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f9fafb;padding:24px 40px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 16px 16px;text-align:center;">
                            <p style="margin:0 0 4px;font-size:13px;color:#9ca3af;font-weight:600;">
                                <a href="https://i-kirtasiye.com" style="color:#0390b1;text-decoration:none;">i-kirtasiye.com</a>
                            </p>
                            <p style="margin:0 0 12px;font-size:12px;color:#9ca3af;">
                                Türkiye'nin İlk Komisyonsuz B2B Kırtasiye Pazaryeri
                            </p>
                            <p style="margin:0;font-size:11px;color:#d1d5db;">
                                Bu e-posta otomatik olarak gönderilmiştir. Yanıtlamayınız.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
