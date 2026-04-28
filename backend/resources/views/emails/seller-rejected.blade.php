<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hesap Başvurunuz Hakkında</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #fafaf7; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 32px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); overflow: hidden;">
                    <tr>
                        <td style="background: #b8651a; padding: 24px 32px; color: #ffffff;">
                            <h1 style="margin: 0; font-size: 22px;">i-kirtasiye</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px;">
                            <h2 style="color: #1a1a17; margin-top: 0;">Merhaba {{ $user->business_name ?? $user->email }},</h2>
                            <p style="color: #1a1a17; line-height: 1.6;">
                                i-kirtasiye B2B platformuna olan başvurunuz incelenmiştir. Maalesef bu aşamada başvurunuz <strong>onaylanamamıştır</strong>.
                            </p>
                            <p style="color: #1a1a17; line-height: 1.6;"><strong>Ret sebebi:</strong></p>
                            <p style="background: #fbe9e9; border-left: 4px solid #b42525; padding: 12px 16px; color: #1a1a17; line-height: 1.6;">
                                {{ $reason }}
                            </p>
                            <p style="color: #5a5a52; line-height: 1.6;">
                                Eksik bilgi veya belgelerinizi tamamlayarak tekrar başvurabilirsiniz. Sorularınız için iletişime geçebilirsiniz.
                            </p>
                            <p style="color: #5a5a52; font-size: 13px; line-height: 1.6;">
                                <a href="mailto:destek@i-kirtasiye.com" style="color: #b8651a;">destek@i-kirtasiye.com</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #f3f3ee; padding: 16px 32px; color: #5a5a52; font-size: 12px;">
                            &copy; {{ date('Y') }} i-kirtasiye. Tüm hakları saklıdır.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
