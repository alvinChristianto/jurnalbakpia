<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f4f5; font-family:Arial, Helvetica, sans-serif; color:#27272a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e4e4e7;">
                    <tr>
                        <td style="padding:24px 32px; background-color:#7c2d12; color:#ffffff;">
                            <h1 style="margin:0; font-size:20px;">Bakpia 3 Generasi</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin:0 0 16px; font-size:18px; color:#27272a;">Verifikasi Email Anda</h2>
                            <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                                Halo {{ $name }},
                            </p>
                            <p style="margin:0 0 24px; font-size:14px; line-height:1.6;">
                                Terima kasih telah mendaftar. Klik tombol di bawah untuk memverifikasi alamat email Anda dan mengaktifkan akun sepenuhnya.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td align="center" style="border-radius:8px; background-color:#7c2d12;">
                                        <a href="{{ $verifyUrl }}" target="_blank" style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:8px;">
                                            Verifikasi Email
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 8px; font-size:12px; line-height:1.6; color:#71717a;">
                                Tautan ini berlaku selama <strong>24 jam</strong>. Jika Anda tidak membuat akun ini, abaikan email ini.
                            </p>
                            <p style="margin:16px 0 0; font-size:12px; line-height:1.6; color:#71717a; word-break:break-all;">
                                Jika tombol tidak berfungsi, salin tautan ini: {{ $verifyUrl }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
