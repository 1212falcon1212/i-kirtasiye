<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Hesabınız Onaylandı</title>
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
                            <h2 style="color: #1a1a17; margin-top: 0;">Hoş geldiniz, {{ $user->business_name ?? $user->email }}!</h2>
                            <p style="color: #1a1a17; line-height: 1.6;">
                                <strong>Hesabınız başarıyla onaylandı.</strong> i-kirtasiye B2B platformuna katıldığınız için teşekkür ederiz.
                            </p>
                            <p style="color: #5a5a52; line-height: 1.6;">
                                Artık platforma giriş yaparak kırtasiye ürünleri için teklif alabilir, sipariş oluşturabilir ve tedarikçilerle çalışabilirsiniz.
                            </p>
                            <p style="margin: 32px 0;">
                                <a href="{{ config('app.url') }}/login" style="background: #b8651a; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;">Hesabıma Giriş Yap</a>
                            </p>
                            <p style="color: #5a5a52; font-size: 13px; line-height: 1.6;">
                                Sorularınız için <a href="mailto:destek@i-kirtasiye.com" style="color: #b8651a;">destek@i-kirtasiye.com</a> adresinden bize ulaşabilirsiniz.
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
