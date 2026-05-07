<!DOCTYPE html>
<html lang="tr" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Şifre Sıfırlama — i-kirtasiye</title>
    <!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f0fdfa; color: #1a1a1a; }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#f0fdfa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <!-- Preheader (hidden text for email clients) -->
    <div style="display:none;font-size:1px;color:#f0fdfa;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        Hesabınız için şifre sıfırlama bağlantınız hazır. 60 dakika içinde geçerlidir.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f0fdfa;">
        <tr>
            <td align="center" style="padding:40px 20px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;">

                    <!-- Header -->
                    <tr>
                        <td align="center" style="background:linear-gradient(135deg,#0390b1,#027a96);padding:40px 40px 32px;border-radius:16px 16px 0 0;">
                            <h1 style="margin:0 0 6px;font-size:32px;font-weight:800;color:#ffffff;letter-spacing:0.5px;">i-kirtasiye</h1>
                            <p style="margin:0;font-size:14px;color:rgba(255,255,255,0.85);font-weight:400;">B2B Kırtasiye Pazaryeri</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background:#ffffff;padding:40px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">

                            <!-- Lock Icon -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-bottom:24px;">
                                        <div style="width:64px;height:64px;border-radius:50%;background:#ecfeff;display:inline-block;line-height:64px;text-align:center;font-size:28px;">
                                            &#128274;
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#1a1a1a;">
                                Sayın {{ $user->business_name ?? $user->nickname ?? 'Değerli Üyemiz' }},
                            </h2>

                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#6b7280;">
                                Hesabınız için bir şifre sıfırlama talebi aldık. Şifrenizi yenilemek için aşağıdaki butona tıklayın:
                            </p>

                            <!-- CTA Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="padding:8px 0 32px;">
                                        <a href="{{ $resetUrl }}" target="_blank" style="display:inline-block;background:#0390b1;color:#ffffff;padding:16px 40px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:700;letter-spacing:0.3px;mso-padding-alt:0;text-align:center;">
                                            <!--[if mso]><i style="mso-font-width:200%;mso-text-raise:24pt" hidden>&emsp;</i><![endif]-->
                                            Şifremi Sıfırla
                                            <!--[if mso]><i style="mso-font-width:200%;" hidden>&emsp;&#8203;</i><![endif]-->
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Link fallback -->
                            <p style="margin:0 0 24px;font-size:13px;line-height:1.6;color:#9ca3af;text-align:center;word-break:break-all;">
                                Buton çalışmıyorsa bu bağlantıyı tarayıcınıza kopyalayın:<br>
                                <a href="{{ $resetUrl }}" style="color:#0390b1;">{{ $resetUrl }}</a>
                            </p>

                            <!-- Divider -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr><td style="border-top:1px solid #e5e7eb;padding-top:24px;"></td></tr>
                            </table>

                            <!-- Warning Box -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:16px 20px;">
                                        <p style="margin:0;font-size:14px;line-height:1.6;color:#92400e;">
                                            <strong>&#9888;&#65039; Önemli:</strong> Bu bağlantı <strong>60 dakika</strong> içinde geçerliliğini yitirecektir. Eğer bu talebi siz yapmadıysanız, bu e-postayı görmezden gelebilirsiniz — şifreniz değiştirilmeyecektir.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Note -->
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-top:20px;">
                                <tr>
                                    <td style="background:#f9fafb;border-radius:8px;padding:14px 20px;">
                                        <p style="margin:0;font-size:13px;line-height:1.6;color:#6b7280;">
                                            &#128272; Güvenlik notu: i-kirtasiye ekibi sizden asla e-posta ile şifrenizi istemez. Şüpheli bir durum fark ederseniz <a href="mailto:info@i-kirtasiye.com" style="color:#0390b1;text-decoration:none;font-weight:600;">info@i-kirtasiye.com</a> adresine bildiriniz.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:32px 0 0;font-size:15px;line-height:1.7;color:#6b7280;">
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
