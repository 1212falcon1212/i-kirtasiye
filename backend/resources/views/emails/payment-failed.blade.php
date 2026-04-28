<!DOCTYPE html>
<html lang="tr" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Ödeme Başarısız — i-kirtasiye</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f0fdfa; color: #1a1a1a; }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#f0fdfa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <div style="display:none;font-size:1px;color:#f0fdfa;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        Sipariş #{{ $order->order_number }} için ödeme işlemi tamamlanamadı. Lütfen tekrar deneyiniz.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f0fdfa;">
        <tr>
            <td align="center" style="padding:40px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;">

                    <!-- Header (Red gradient for error) -->
                    <tr>
                        <td align="center" style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:40px 40px 32px;border-radius:16px 16px 0 0;">
                            <h1 style="margin:0 0 6px;font-size:32px;font-weight:800;color:#ffffff;letter-spacing:0.5px;">i-kirtasiye</h1>
                            <p style="margin:0;font-size:14px;color:rgba(255,255,255,0.85);font-weight:400;">Ödeme işlemi başarısız oldu</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background:#ffffff;padding:40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <!-- Error Icon -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-bottom:24px;">
                                        <div style="width:72px;height:72px;border-radius:50%;background:#fef2f2;border:3px solid #dc2626;display:inline-block;line-height:72px;text-align:center;font-size:36px;color:#dc2626;">
                                            &#10007;
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#1a1a1a;">
                                Sayın {{ $order->user->business_name ?? $order->user->name ?? 'Değerli Müşterimiz' }},
                            </h2>

                            <p style="margin:0 0 28px;font-size:15px;line-height:1.7;color:#6b7280;">
                                Siparişiniz için ödeme işlemi tamamlanamadı. Siparişiniz hala aktif, lütfen tekrar deneyiniz.
                            </p>

                            <!-- Order Info -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:24px;">
                                <tr>
                                    <td style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:20px 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="padding:6px 0;font-size:14px;color:#6b7280;">Sipariş No:</td>
                                                <td style="padding:6px 0;font-size:14px;font-weight:700;color:#1a1a1a;text-align:right;">#{{ $order->order_number }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;font-size:14px;color:#6b7280;">Tutar:</td>
                                                <td style="padding:6px 0;font-size:14px;font-weight:700;color:#1a1a1a;text-align:right;">{{ number_format($order->total_amount, 2, ',', '.') }} &#8378;</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:6px 0;font-size:14px;color:#6b7280;">Durum:</td>
                                                <td style="padding:6px 0;text-align:right;">
                                                    <span style="display:inline-block;background:#dc2626;color:#ffffff;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:700;">Başarısız</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Error Reason -->
                            @if($reason)
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:24px;">
                                <tr>
                                    <td style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:16px 20px;">
                                        <p style="margin:0;font-size:14px;line-height:1.6;color:#991b1b;">
                                            <strong>Hata Sebebi:</strong> {{ $reason }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <!-- Troubleshooting Steps -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:28px;">
                                <tr>
                                    <td style="background:#fef3c7;border:1px solid #fde68a;border-radius:12px;padding:20px 24px;">
                                        <p style="margin:0 0 12px;font-size:14px;font-weight:700;color:#92400e;">
                                            Lütfen aşağıdakileri kontrol ediniz:
                                        </p>
                                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="padding:4px 0;font-size:14px;color:#92400e;line-height:1.6;">
                                                    &#8226;&nbsp; Kart bilgilerinizin doğru girildiğinden emin olun
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0;font-size:14px;color:#92400e;line-height:1.6;">
                                                    &#8226;&nbsp; Kart limitinizin yeterli olduğunu teyit edin
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0;font-size:14px;color:#92400e;line-height:1.6;">
                                                    &#8226;&nbsp; 3D Secure onayını tamamladığınızdan emin olun
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0;font-size:14px;color:#92400e;line-height:1.6;">
                                                    &#8226;&nbsp; Sorun devam ederse bankanız ile iletişime geçin
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding:8px 0 24px;">
                                        <a href="https://i-kirtasiye.com/market/hesabim?tab=siparislerim" target="_blank" style="display:inline-block;background:#0390b1;color:#ffffff;padding:16px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:700;letter-spacing:0.3px;">
                                            Tekrar Dene
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:14px;line-height:1.6;color:#9ca3af;text-align:center;">
                                Sorun devam ederse <a href="mailto:info@i-kirtasiye.com" style="color:#0390b1;text-decoration:none;font-weight:600;">info@i-kirtasiye.com</a> adresinden destek alabilirsiniz.
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
                                Türkiye'nin İlk Komisyonsuz B2B Dermokozmetik Pazaryeri
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
